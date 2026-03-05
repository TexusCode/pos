using System.Net.Http.Headers;
using System.Net;
using System.Text.RegularExpressions;
using System.Text;
using System.Text.Json;
using System.Text.Json.Serialization;
using PosDesktop.Models.Api;

namespace PosDesktop.Services;

public sealed class PosApiClient
{
    private readonly HttpClient _httpClient;
    private readonly JsonSerializerOptions _jsonOptions = new()
    {
        PropertyNameCaseInsensitive = true,
        NumberHandling = JsonNumberHandling.AllowReadingFromString,
    };

    private readonly string _baseUrl;
    private const string ApiPrefix = "/api/v1";

    public PosApiClient(string baseUrl)
    {
        _baseUrl = baseUrl.TrimEnd('/');
        _httpClient = new HttpClient
        {
            Timeout = TimeSpan.FromSeconds(30),
        };
        _httpClient.DefaultRequestHeaders.Accept.Clear();
        _httpClient.DefaultRequestHeaders.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));
    }

    public void SetBearerToken(string token)
    {
        _httpClient.DefaultRequestHeaders.Authorization = new AuthenticationHeaderValue("Bearer", token);
    }

    public void ClearBearerToken()
    {
        _httpClient.DefaultRequestHeaders.Authorization = null;
    }

    public Task<LoginResponse> LoginAsync(LoginRequest request, CancellationToken cancellationToken = default)
    {
        return SendAsync<LoginResponse>(HttpMethod.Post, $"{ApiPrefix}/auth/login", request, cancellationToken);
    }

    public Task<MeResponse> MeAsync(CancellationToken cancellationToken = default)
    {
        return SendAsync<MeResponse>(HttpMethod.Get, $"{ApiPrefix}/auth/me", null, cancellationToken);
    }

    public async Task LogoutAsync(CancellationToken cancellationToken = default)
    {
        await SendAsync<JsonElement>(HttpMethod.Post, $"{ApiPrefix}/auth/logout", new { all_devices = false }, cancellationToken);
    }

    public Task<ShiftCurrentResponse> GetCurrentShiftAsync(CancellationToken cancellationToken = default)
    {
        return SendAsync<ShiftCurrentResponse>(HttpMethod.Get, $"{ApiPrefix}/shift/current", null, cancellationToken);
    }

    public Task<ShiftActionResponse> OpenShiftAsync(decimal initialCash, CancellationToken cancellationToken = default)
    {
        return SendAsync<ShiftActionResponse>(
            HttpMethod.Post,
            $"{ApiPrefix}/shift/open",
            new { initial_cash = initialCash },
            cancellationToken);
    }

    public Task<ShiftActionResponse> CloseShiftAsync(decimal finalCash, CancellationToken cancellationToken = default)
    {
        return SendAsync<ShiftActionResponse>(
            HttpMethod.Post,
            $"{ApiPrefix}/shift/close",
            new { final_cash = finalCash },
            cancellationToken);
    }

    public Task<ProductListResponse> GetProductsAsync(
        string? search,
        string? status = null,
        int? perPage = null,
        int? page = null,
        CancellationToken cancellationToken = default)
    {
        var queryParts = new List<string>();
        if (!string.IsNullOrWhiteSpace(search))
        {
            queryParts.Add($"search={Uri.EscapeDataString(search)}");
        }

        if (!string.IsNullOrWhiteSpace(status))
        {
            queryParts.Add($"status={Uri.EscapeDataString(status)}");
        }

        if (perPage.HasValue && perPage.Value > 0)
        {
            queryParts.Add($"per_page={perPage.Value}");
        }

        if (page.HasValue && page.Value > 0)
        {
            queryParts.Add($"page={page.Value}");
        }

        var query = queryParts.Count == 0
            ? string.Empty
            : "?" + string.Join("&", queryParts);

        return SendAsync<ProductListResponse>(HttpMethod.Get, $"{ApiPrefix}/products{query}", null, cancellationToken);
    }

    public Task<ProductEnvelope> GetProductBySkuAsync(string sku, CancellationToken cancellationToken = default)
    {
        return SendAsync<ProductEnvelope>(
            HttpMethod.Get,
            $"{ApiPrefix}/products/by-sku/{Uri.EscapeDataString(sku)}",
            null,
            cancellationToken);
    }

    public Task<CartListResponse> GetCartsAsync(CancellationToken cancellationToken = default)
    {
        return SendAsync<CartListResponse>(HttpMethod.Get, $"{ApiPrefix}/carts", null, cancellationToken);
    }

    public Task<CartActionResponse> CreateCartAsync(CancellationToken cancellationToken = default)
    {
        return SendAsync<CartActionResponse>(HttpMethod.Post, $"{ApiPrefix}/carts", new { }, cancellationToken);
    }

    public Task<CartEnvelope> GetCartAsync(int cartId, CancellationToken cancellationToken = default)
    {
        return SendAsync<CartEnvelope>(HttpMethod.Get, $"{ApiPrefix}/carts/{cartId}", null, cancellationToken);
    }

    public Task<CartActionResponse> AddItemToCartAsync(
        int cartId,
        AddCartItemRequest request,
        CancellationToken cancellationToken = default)
    {
        return SendAsync<CartActionResponse>(HttpMethod.Post, $"{ApiPrefix}/carts/{cartId}/items", request, cancellationToken);
    }

    public Task<CartActionResponse> UpdateCartItemAsync(
        int cartId,
        int itemId,
        UpdateCartItemRequest request,
        CancellationToken cancellationToken = default)
    {
        return SendAsync<CartActionResponse>(
            HttpMethod.Patch,
            $"{ApiPrefix}/carts/{cartId}/items/{itemId}",
            request,
            cancellationToken);
    }

    public Task<CartActionResponse> RemoveCartItemAsync(int cartId, int itemId, CancellationToken cancellationToken = default)
    {
        return SendAsync<CartActionResponse>(
            HttpMethod.Delete,
            $"{ApiPrefix}/carts/{cartId}/items/{itemId}",
            null,
            cancellationToken);
    }

    public Task<CartActionResponse> ApplyDiscountAsync(
        int cartId,
        ApplyDiscountRequest request,
        CancellationToken cancellationToken = default)
    {
        return SendAsync<CartActionResponse>(HttpMethod.Post, $"{ApiPrefix}/carts/{cartId}/discount", request, cancellationToken);
    }

    public Task<CheckoutResponse> CheckoutAsync(int cartId, CheckoutRequest request, CancellationToken cancellationToken = default)
    {
        return SendAsync<CheckoutResponse>(HttpMethod.Post, $"{ApiPrefix}/carts/{cartId}/checkout", request, cancellationToken);
    }

    public async Task DeleteCartAsync(int cartId, CancellationToken cancellationToken = default)
    {
        await SendAsync<JsonElement>(HttpMethod.Delete, $"{ApiPrefix}/carts/{cartId}", null, cancellationToken);
    }

    private async Task<T> SendAsync<T>(
        HttpMethod method,
        string path,
        object? body,
        CancellationToken cancellationToken)
    {
        using var request = new HttpRequestMessage(method, $"{_baseUrl}{path}");

        if (body is not null)
        {
            var payload = JsonSerializer.Serialize(body, _jsonOptions);
            request.Content = new StringContent(payload, Encoding.UTF8, "application/json");
        }

        using var response = await _httpClient.SendAsync(request, cancellationToken);
        var responseText = await response.Content.ReadAsStringAsync(cancellationToken);

        if (!response.IsSuccessStatusCode)
        {
            var message = ExtractApiErrorMessage(responseText);
            throw new ApiException(message, (int)response.StatusCode);
        }

        if (string.IsNullOrWhiteSpace(responseText))
        {
            return default!;
        }

        try
        {
            var result = JsonSerializer.Deserialize<T>(responseText, _jsonOptions);
            if (result is null)
            {
                throw new ApiException("Server returned empty response", (int)response.StatusCode);
            }

            return result;
        }
        catch (JsonException ex)
        {
            var snippet = responseText.Length > 220 ? responseText[..220] : responseText;
            throw new ApiException($"Ошибка формата ответа API: {ex.Message}. Ответ: {snippet}", (int)response.StatusCode);
        }
    }

    private static string ExtractApiErrorMessage(string responseText)
    {
        if (string.IsNullOrWhiteSpace(responseText))
        {
            return "Unknown API error";
        }

        if (LooksLikeHtml(responseText))
        {
            var decoded = WebUtility.HtmlDecode(responseText);

            if (decoded.Contains("api_access_tokens", StringComparison.OrdinalIgnoreCase)
                && decoded.Contains("doesn't exist", StringComparison.OrdinalIgnoreCase))
            {
                return "Ошибка сервера: отсутствует таблица api_access_tokens. Выполните миграции на хостинге.";
            }

            var sqlStateMatch = Regex.Match(decoded, @"SQLSTATE\[[^\]]+\][^\r\n<]{0,350}", RegexOptions.IgnoreCase);
            if (sqlStateMatch.Success)
            {
                return "Ошибка API: " + sqlStateMatch.Value.Trim();
            }

            return "Сервер вернул HTML-ошибку вместо JSON. Проверьте миграции и логи Laravel.";
        }

        try
        {
            using var document = JsonDocument.Parse(responseText);
            if (document.RootElement.TryGetProperty("message", out var messageProperty))
            {
                var message = messageProperty.GetString();
                if (!string.IsNullOrWhiteSpace(message))
                {
                    return message;
                }
            }
        }
        catch
        {
            // ignore parse issues
        }

        return responseText.Length > 300 ? responseText[..300] : responseText;
    }

    private static bool LooksLikeHtml(string value)
    {
        return value.Contains("<!DOCTYPE html", StringComparison.OrdinalIgnoreCase)
               || value.Contains("<html", StringComparison.OrdinalIgnoreCase);
    }
}
