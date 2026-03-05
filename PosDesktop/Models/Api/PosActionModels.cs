using System.Text.Json.Serialization;

namespace PosDesktop.Models.Api;

public sealed class AddExpenseRequest
{
    [JsonPropertyName("total")]
    public decimal Total { get; set; }

    [JsonPropertyName("description")]
    public string? Description { get; set; }
}

public sealed class ExpenseActionResponse
{
    [JsonPropertyName("message")]
    public string Message { get; set; } = string.Empty;

    [JsonPropertyName("expense")]
    public ExpenseDto Expense { get; set; } = new();
}

public sealed class ExpenseDto
{
    [JsonPropertyName("id")]
    public int Id { get; set; }

    [JsonPropertyName("shift_id")]
    public int ShiftId { get; set; }

    [JsonPropertyName("total")]
    public decimal Total { get; set; }

    [JsonPropertyName("description")]
    public string? Description { get; set; }

    [JsonPropertyName("created_at")]
    public string? CreatedAt { get; set; }
}

public sealed class CustomerLookupResponse
{
    [JsonPropertyName("customer")]
    public CustomerShortDto? Customer { get; set; }
}

public sealed class CustomerShortDto
{
    [JsonPropertyName("id")]
    public int Id { get; set; }

    [JsonPropertyName("name")]
    public string Name { get; set; } = string.Empty;

    [JsonPropertyName("phone")]
    public string Phone { get; set; } = string.Empty;

    [JsonPropertyName("debt")]
    public decimal Debt { get; set; }
}

public sealed class PayDebtRequest
{
    [JsonPropertyName("phone")]
    public string Phone { get; set; } = string.Empty;

    [JsonPropertyName("total")]
    public decimal Total { get; set; }

    [JsonPropertyName("customer_name")]
    public string? CustomerName { get; set; }
}

public sealed class DebtPaymentResponse
{
    [JsonPropertyName("message")]
    public string Message { get; set; } = string.Empty;

    [JsonPropertyName("customer")]
    public CustomerShortDto Customer { get; set; } = new();

    [JsonPropertyName("payment")]
    public DebtPaymentDto Payment { get; set; } = new();
}

public sealed class DebtPaymentDto
{
    [JsonPropertyName("amount")]
    public decimal Amount { get; set; }

    [JsonPropertyName("type")]
    public string Type { get; set; } = string.Empty;

    [JsonPropertyName("shift_id")]
    public int ShiftId { get; set; }
}

public sealed class UpsertProductStockRequest
{
    [JsonPropertyName("sku")]
    public string Sku { get; set; } = string.Empty;

    [JsonPropertyName("quantity")]
    public int Quantity { get; set; }

    [JsonPropertyName("name")]
    public string? Name { get; set; }

    [JsonPropertyName("selling_price")]
    public decimal? SellingPrice { get; set; }

    [JsonPropertyName("status")]
    public string? Status { get; set; }
}

public sealed class UpsertProductStockResponse
{
    [JsonPropertyName("message")]
    public string Message { get; set; } = string.Empty;

    [JsonPropertyName("created")]
    public bool Created { get; set; }

    [JsonPropertyName("product")]
    public ProductDto Product { get; set; } = new();
}

