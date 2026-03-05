using System.Globalization;
using System.Text.Json;
using Microsoft.Data.Sqlite;
using PosDesktop.Models.Api;
using PosDesktop.Models.Local;

namespace PosDesktop.Services;

public sealed class LocalPosStore
{
    private const string MetaCachedUser = "cached_user";
    private const string MetaCachedShift = "cached_shift";
    private const string MetaPendingShiftOpen = "pending_shift_open";
    private const string MetaPendingShiftClose = "pending_shift_close";

    private readonly string _dbPath;
    private readonly JsonSerializerOptions _jsonOptions = new()
    {
        PropertyNameCaseInsensitive = true,
    };

    public LocalPosStore()
    {
        _dbPath = AppDataPaths.GetDatabasePath();
    }

    public async Task InitializeAsync()
    {
        await using var connection = OpenConnection();
        await connection.OpenAsync();

        var command = connection.CreateCommand();
        command.CommandText =
            """
            CREATE TABLE IF NOT EXISTS meta (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY,
                payload TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS carts (
                client_id INTEGER PRIMARY KEY,
                server_id INTEGER NULL,
                user_id TEXT NOT NULL,
                payload TEXT NOT NULL,
                state TEXT NOT NULL,
                is_dirty INTEGER NOT NULL DEFAULT 0,
                pending_checkout TEXT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE UNIQUE INDEX IF NOT EXISTS idx_carts_server_id
            ON carts(server_id)
            WHERE server_id IS NOT NULL;

            CREATE INDEX IF NOT EXISTS idx_carts_user_id
            ON carts(user_id);
            """;

        await command.ExecuteNonQueryAsync();
    }

    public async Task SaveCachedUserAsync(UserDto user)
    {
        await SaveMetaAsync(MetaCachedUser, Serialize(user));
    }

    public async Task<UserDto?> LoadCachedUserAsync()
    {
        var payload = await LoadMetaAsync(MetaCachedUser);
        return Deserialize<UserDto>(payload);
    }

    public async Task SaveShiftAsync(ShiftDto? shift)
    {
        if (shift is null)
        {
            await RemoveMetaAsync(MetaCachedShift);
            return;
        }

        await SaveMetaAsync(MetaCachedShift, Serialize(shift));
    }

    public async Task<ShiftDto?> LoadShiftAsync()
    {
        var payload = await LoadMetaAsync(MetaCachedShift);
        return Deserialize<ShiftDto>(payload);
    }

    public Task SetPendingShiftOpenAsync(decimal initialCash)
    {
        return SaveMetaAsync(MetaPendingShiftOpen, initialCash.ToString(CultureInfo.InvariantCulture));
    }

    public async Task<decimal?> GetPendingShiftOpenAsync()
    {
        var payload = await LoadMetaAsync(MetaPendingShiftOpen);
        return ParseDecimalInvariant(payload);
    }

    public Task ClearPendingShiftOpenAsync()
    {
        return RemoveMetaAsync(MetaPendingShiftOpen);
    }

    public Task SetPendingShiftCloseAsync(decimal finalCash)
    {
        return SaveMetaAsync(MetaPendingShiftClose, finalCash.ToString(CultureInfo.InvariantCulture));
    }

    public async Task<decimal?> GetPendingShiftCloseAsync()
    {
        var payload = await LoadMetaAsync(MetaPendingShiftClose);
        return ParseDecimalInvariant(payload);
    }

    public Task ClearPendingShiftCloseAsync()
    {
        return RemoveMetaAsync(MetaPendingShiftClose);
    }

    public async Task SaveProductsAsync(IReadOnlyCollection<ProductDto> products)
    {
        await using var connection = OpenConnection();
        await connection.OpenAsync();
        await using var transaction = connection.BeginTransaction();

        var clearCommand = connection.CreateCommand();
        clearCommand.Transaction = transaction;
        clearCommand.CommandText = "DELETE FROM products;";
        await clearCommand.ExecuteNonQueryAsync();

        foreach (var product in products)
        {
            var insertCommand = connection.CreateCommand();
            insertCommand.Transaction = transaction;
            insertCommand.CommandText =
                """
                INSERT INTO products(id, payload, updated_at)
                VALUES (@id, @payload, @updated_at);
                """;
            insertCommand.Parameters.AddWithValue("@id", product.Id);
            insertCommand.Parameters.AddWithValue("@payload", Serialize(product));
            insertCommand.Parameters.AddWithValue("@updated_at", DateTime.UtcNow.ToString("O"));

            await insertCommand.ExecuteNonQueryAsync();
        }

        await transaction.CommitAsync();
    }

    public async Task<List<ProductDto>> LoadProductsAsync(string? search = null, bool activeOnly = false)
    {
        var products = new List<ProductDto>();

        await using var connection = OpenConnection();
        await connection.OpenAsync();

        var command = connection.CreateCommand();
        command.CommandText = "SELECT payload FROM products ORDER BY id DESC;";

        await using var reader = await command.ExecuteReaderAsync();
        while (await reader.ReadAsync())
        {
            var payload = reader.GetString(0);
            var product = Deserialize<ProductDto>(payload);
            if (product is null)
            {
                continue;
            }

            products.Add(product);
        }

        if (activeOnly)
        {
            products = products
                .Where(p => string.Equals(p.Status, "active", StringComparison.OrdinalIgnoreCase))
                .ToList();
        }

        if (string.IsNullOrWhiteSpace(search))
        {
            return products;
        }

        var term = search.Trim();
        return products
            .Where(p =>
                p.Name.Contains(term, StringComparison.OrdinalIgnoreCase) ||
                (!string.IsNullOrWhiteSpace(p.Sku) && p.Sku.Contains(term, StringComparison.OrdinalIgnoreCase)))
            .ToList();
    }

    public async Task SaveCartRecordAsync(LocalCartRecord record)
    {
        record.UpdatedAtUtc = DateTime.UtcNow;
        record.Cart.Id = record.ClientId;
        record.Cart.UserId = record.UserId;

        await using var connection = OpenConnection();
        await connection.OpenAsync();

        var command = connection.CreateCommand();
        command.CommandText =
            """
            INSERT INTO carts(client_id, server_id, user_id, payload, state, is_dirty, pending_checkout, updated_at)
            VALUES (@client_id, @server_id, @user_id, @payload, @state, @is_dirty, @pending_checkout, @updated_at)
            ON CONFLICT(client_id) DO UPDATE SET
                server_id = excluded.server_id,
                user_id = excluded.user_id,
                payload = excluded.payload,
                state = excluded.state,
                is_dirty = excluded.is_dirty,
                pending_checkout = excluded.pending_checkout,
                updated_at = excluded.updated_at;
            """;

        command.Parameters.AddWithValue("@client_id", record.ClientId);
        if (record.ServerId.HasValue)
        {
            command.Parameters.AddWithValue("@server_id", record.ServerId.Value);
        }
        else
        {
            command.Parameters.AddWithValue("@server_id", DBNull.Value);
        }

        command.Parameters.AddWithValue("@user_id", record.UserId);
        command.Parameters.AddWithValue("@payload", Serialize(record.Cart));
        command.Parameters.AddWithValue("@state", record.State);
        command.Parameters.AddWithValue("@is_dirty", record.IsDirty ? 1 : 0);

        if (record.PendingCheckout is not null)
        {
            command.Parameters.AddWithValue("@pending_checkout", Serialize(record.PendingCheckout));
        }
        else
        {
            command.Parameters.AddWithValue("@pending_checkout", DBNull.Value);
        }

        command.Parameters.AddWithValue("@updated_at", record.UpdatedAtUtc.ToString("O"));

        await command.ExecuteNonQueryAsync();
    }

    public async Task<LocalCartRecord?> GetCartRecordAsync(string userId, int clientId)
    {
        await using var connection = OpenConnection();
        await connection.OpenAsync();

        var command = connection.CreateCommand();
        command.CommandText =
            """
            SELECT client_id, server_id, user_id, payload, state, is_dirty, pending_checkout, updated_at
            FROM carts
            WHERE user_id = @user_id AND client_id = @client_id
            LIMIT 1;
            """;
        command.Parameters.AddWithValue("@user_id", userId);
        command.Parameters.AddWithValue("@client_id", clientId);

        await using var reader = await command.ExecuteReaderAsync();
        if (!await reader.ReadAsync())
        {
            return null;
        }

        return ReadCartRecord(reader);
    }

    public async Task<List<LocalCartRecord>> GetCartRecordsAsync(string userId)
    {
        var records = new List<LocalCartRecord>();

        await using var connection = OpenConnection();
        await connection.OpenAsync();

        var command = connection.CreateCommand();
        command.CommandText =
            """
            SELECT client_id, server_id, user_id, payload, state, is_dirty, pending_checkout, updated_at
            FROM carts
            WHERE user_id = @user_id
            ORDER BY updated_at DESC;
            """;
        command.Parameters.AddWithValue("@user_id", userId);

        await using var reader = await command.ExecuteReaderAsync();
        while (await reader.ReadAsync())
        {
            var record = ReadCartRecord(reader);
            records.Add(record);
        }

        return records;
    }

    public async Task<List<LocalCartRecord>> GetActiveCartRecordsAsync(string userId)
    {
        var all = await GetCartRecordsAsync(userId);
        return all
            .Where(x => x.State == LocalCartStates.Active)
            .OrderByDescending(x => x.UpdatedAtUtc)
            .ToList();
    }

    public async Task<LocalCartRecord> CreateLocalCartAsync(string userId)
    {
        var nextClientId = await GetNextOfflineClientIdAsync(userId);
        var cart = new CartDto
        {
            Id = nextClientId,
            UserId = userId,
            Discount = 0,
            Subtotal = 0,
            Total = 0,
            Items = [],
        };

        var record = new LocalCartRecord
        {
            ClientId = nextClientId,
            UserId = userId,
            ServerId = null,
            Cart = cart,
            State = LocalCartStates.Active,
            IsDirty = true,
        };

        await SaveCartRecordAsync(record);
        return record;
    }

    public async Task DeleteCartPermanentlyAsync(string userId, int clientId)
    {
        await using var connection = OpenConnection();
        await connection.OpenAsync();

        var command = connection.CreateCommand();
        command.CommandText =
            """
            DELETE FROM carts
            WHERE user_id = @user_id AND client_id = @client_id;
            """;
        command.Parameters.AddWithValue("@user_id", userId);
        command.Parameters.AddWithValue("@client_id", clientId);
        await command.ExecuteNonQueryAsync();
    }

    public async Task MergeServerCartsAsync(string userId, IReadOnlyList<CartDto> serverCarts)
    {
        var existing = await GetCartRecordsAsync(userId);
        var byServerId = existing
            .Where(x => x.ServerId.HasValue)
            .ToDictionary(x => x.ServerId!.Value, x => x);
        var incomingServerIds = new HashSet<int>(serverCarts.Select(x => x.Id));

        foreach (var serverCart in serverCarts)
        {
            if (byServerId.TryGetValue(serverCart.Id, out var found))
            {
                if (found.IsDirty || found.State != LocalCartStates.Active)
                {
                    continue;
                }

                found.Cart = CloneServerCart(serverCart, found.ClientId);
                found.IsDirty = false;
                found.State = LocalCartStates.Active;
                found.PendingCheckout = null;
                await SaveCartRecordAsync(found);
                continue;
            }

            var clientId = await GetPreferredClientIdForServerAsync(userId, serverCart.Id);
            var fresh = new LocalCartRecord
            {
                ClientId = clientId,
                ServerId = serverCart.Id,
                UserId = userId,
                Cart = CloneServerCart(serverCart, clientId),
                State = LocalCartStates.Active,
                IsDirty = false,
                PendingCheckout = null,
            };

            await SaveCartRecordAsync(fresh);
        }

        foreach (var record in existing)
        {
            if (record.ServerId is null)
            {
                continue;
            }

            if (incomingServerIds.Contains(record.ServerId.Value))
            {
                continue;
            }

            if (record.IsDirty || record.State != LocalCartStates.Active)
            {
                continue;
            }

            await DeleteCartPermanentlyAsync(userId, record.ClientId);
        }
    }

    public async Task ClearUserCartsAsync(string userId)
    {
        await using var connection = OpenConnection();
        await connection.OpenAsync();

        var command = connection.CreateCommand();
        command.CommandText = "DELETE FROM carts WHERE user_id = @user_id;";
        command.Parameters.AddWithValue("@user_id", userId);
        await command.ExecuteNonQueryAsync();
    }

    public async Task ClearAllAsync()
    {
        await using var connection = OpenConnection();
        await connection.OpenAsync();

        var command = connection.CreateCommand();
        command.CommandText =
            """
            DELETE FROM carts;
            DELETE FROM products;
            DELETE FROM meta;
            """;
        await command.ExecuteNonQueryAsync();
    }

    private LocalCartRecord ReadCartRecord(SqliteDataReader reader)
    {
        var clientId = reader.GetInt32(0);
        var serverId = reader.IsDBNull(1) ? (int?)null : reader.GetInt32(1);
        var userId = reader.GetString(2);
        var payload = reader.GetString(3);
        var state = reader.GetString(4);
        var isDirty = reader.GetInt32(5) == 1;
        var pendingPayload = reader.IsDBNull(6) ? null : reader.GetString(6);
        var updatedAtRaw = reader.GetString(7);

        var cart = Deserialize<CartDto>(payload) ?? new CartDto();
        cart.Id = clientId;
        cart.UserId = userId;

        var pending = Deserialize<CheckoutRequest>(pendingPayload);
        var updatedAt = DateTime.TryParse(
            updatedAtRaw,
            CultureInfo.InvariantCulture,
            DateTimeStyles.AssumeUniversal | DateTimeStyles.AdjustToUniversal,
            out var parsedUpdatedAt)
            ? parsedUpdatedAt
            : DateTime.UtcNow;

        return new LocalCartRecord
        {
            ClientId = clientId,
            ServerId = serverId,
            UserId = userId,
            Cart = cart,
            State = state,
            IsDirty = isDirty,
            PendingCheckout = pending,
            UpdatedAtUtc = updatedAt,
        };
    }

    private static CartDto CloneServerCart(CartDto source, int clientId)
    {
        return new CartDto
        {
            Id = clientId,
            UserId = source.UserId,
            Discount = source.Discount,
            Subtotal = source.Subtotal,
            Total = source.Total,
            Items = source.Items
                .Select(item => new CartItemDto
                {
                    Id = item.Id,
                    ProductId = item.ProductId,
                    ProductName = item.ProductName,
                    ProductSku = item.ProductSku,
                    Price = item.Price,
                    Quantity = item.Quantity,
                    Discount = item.Discount,
                    LineSubtotal = item.LineSubtotal,
                })
                .ToList(),
        };
    }

    private async Task<int> GetNextOfflineClientIdAsync(string userId)
    {
        await using var connection = OpenConnection();
        await connection.OpenAsync();

        var command = connection.CreateCommand();
        command.CommandText =
            """
            SELECT MIN(client_id)
            FROM carts
            WHERE user_id = @user_id;
            """;
        command.Parameters.AddWithValue("@user_id", userId);
        var minValue = await command.ExecuteScalarAsync();

        if (minValue is null || minValue == DBNull.Value)
        {
            return -1;
        }

        var minId = Convert.ToInt32(minValue, CultureInfo.InvariantCulture);
        if (minId < 0)
        {
            return minId - 1;
        }

        return -1;
    }

    private async Task<int> GetPreferredClientIdForServerAsync(string userId, int serverId)
    {
        await using var connection = OpenConnection();
        await connection.OpenAsync();

        var existsCommand = connection.CreateCommand();
        existsCommand.CommandText =
            """
            SELECT COUNT(1)
            FROM carts
            WHERE user_id = @user_id AND client_id = @client_id;
            """;
        existsCommand.Parameters.AddWithValue("@user_id", userId);
        existsCommand.Parameters.AddWithValue("@client_id", serverId);
        var exists = Convert.ToInt32(await existsCommand.ExecuteScalarAsync(), CultureInfo.InvariantCulture);

        if (exists == 0)
        {
            return serverId;
        }

        var maxCommand = connection.CreateCommand();
        maxCommand.CommandText =
            """
            SELECT MAX(client_id)
            FROM carts
            WHERE user_id = @user_id AND client_id > 0;
            """;
        maxCommand.Parameters.AddWithValue("@user_id", userId);
        var maxValue = await maxCommand.ExecuteScalarAsync();
        var maxId = maxValue is null || maxValue == DBNull.Value
            ? 0
            : Convert.ToInt32(maxValue, CultureInfo.InvariantCulture);

        return maxId + 1;
    }

    private async Task SaveMetaAsync(string key, string value)
    {
        await using var connection = OpenConnection();
        await connection.OpenAsync();

        var command = connection.CreateCommand();
        command.CommandText =
            """
            INSERT INTO meta(key, value, updated_at)
            VALUES (@key, @value, @updated_at)
            ON CONFLICT(key) DO UPDATE SET
                value = excluded.value,
                updated_at = excluded.updated_at;
            """;
        command.Parameters.AddWithValue("@key", key);
        command.Parameters.AddWithValue("@value", value);
        command.Parameters.AddWithValue("@updated_at", DateTime.UtcNow.ToString("O"));
        await command.ExecuteNonQueryAsync();
    }

    private async Task<string?> LoadMetaAsync(string key)
    {
        await using var connection = OpenConnection();
        await connection.OpenAsync();

        var command = connection.CreateCommand();
        command.CommandText = "SELECT value FROM meta WHERE key = @key LIMIT 1;";
        command.Parameters.AddWithValue("@key", key);
        var value = await command.ExecuteScalarAsync();
        return value as string;
    }

    private async Task RemoveMetaAsync(string key)
    {
        await using var connection = OpenConnection();
        await connection.OpenAsync();

        var command = connection.CreateCommand();
        command.CommandText = "DELETE FROM meta WHERE key = @key;";
        command.Parameters.AddWithValue("@key", key);
        await command.ExecuteNonQueryAsync();
    }

    private SqliteConnection OpenConnection()
    {
        return new SqliteConnection($"Data Source={_dbPath};Cache=Shared");
    }

    private string Serialize<T>(T value)
    {
        return JsonSerializer.Serialize(value, _jsonOptions);
    }

    private T? Deserialize<T>(string? payload)
    {
        if (string.IsNullOrWhiteSpace(payload))
        {
            return default;
        }

        try
        {
            return JsonSerializer.Deserialize<T>(payload, _jsonOptions);
        }
        catch
        {
            return default;
        }
    }

    private static decimal? ParseDecimalInvariant(string? payload)
    {
        if (string.IsNullOrWhiteSpace(payload))
        {
            return null;
        }

        return decimal.TryParse(payload, NumberStyles.Any, CultureInfo.InvariantCulture, out var value)
            ? value
            : null;
    }
}
