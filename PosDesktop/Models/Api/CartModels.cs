using System.Text.Json.Serialization;

namespace PosDesktop.Models.Api;

public sealed class CartListResponse
{
    [JsonPropertyName("items")]
    public List<CartDto> Items { get; set; } = [];
}

public sealed class CartEnvelope
{
    [JsonPropertyName("cart")]
    public CartDto Cart { get; set; } = new();
}

public sealed class CartActionResponse
{
    [JsonPropertyName("message")]
    public string Message { get; set; } = string.Empty;

    [JsonPropertyName("cart")]
    public CartDto Cart { get; set; } = new();
}

public sealed class CheckoutResponse
{
    [JsonPropertyName("message")]
    public string Message { get; set; } = string.Empty;
}

public sealed class CartDto
{
    [JsonPropertyName("id")]
    public int Id { get; set; }

    [JsonPropertyName("user_id")]
    public string UserId { get; set; } = string.Empty;

    [JsonPropertyName("discount")]
    public decimal Discount { get; set; }

    [JsonPropertyName("subtotal")]
    public decimal Subtotal { get; set; }

    [JsonPropertyName("total")]
    public decimal Total { get; set; }

    [JsonPropertyName("items")]
    public List<CartItemDto> Items { get; set; } = [];
}

public sealed class CartItemDto
{
    [JsonPropertyName("id")]
    public int Id { get; set; }

    [JsonPropertyName("product_id")]
    public int ProductId { get; set; }

    [JsonPropertyName("product_name")]
    public string ProductName { get; set; } = string.Empty;

    [JsonPropertyName("product_sku")]
    public string? ProductSku { get; set; }

    [JsonPropertyName("price")]
    public decimal Price { get; set; }

    [JsonPropertyName("quantity")]
    public int Quantity { get; set; }

    [JsonPropertyName("discount")]
    public decimal Discount { get; set; }

    [JsonPropertyName("line_subtotal")]
    public decimal LineSubtotal { get; set; }
}

public sealed class AddCartItemRequest
{
    [JsonPropertyName("product_id")]
    public int? ProductId { get; set; }

    [JsonPropertyName("sku")]
    public string? Sku { get; set; }

    [JsonPropertyName("quantity")]
    public int Quantity { get; set; } = 1;
}

public sealed class UpdateCartItemRequest
{
    [JsonPropertyName("quantity")]
    public int? Quantity { get; set; }

    [JsonPropertyName("discount")]
    public decimal? Discount { get; set; }
}

public sealed class ApplyDiscountRequest
{
    [JsonPropertyName("type")]
    public string Type { get; set; } = "fixed";

    [JsonPropertyName("value")]
    public decimal Value { get; set; }
}

public sealed class CheckoutRequest
{
    [JsonPropertyName("payment_method")]
    public string PaymentMethod { get; set; } = "Наличными";

    [JsonPropertyName("payment_status")]
    public string PaymentStatus { get; set; } = "paid";

    [JsonPropertyName("cash")]
    public decimal Cash { get; set; }

    [JsonPropertyName("customer_name")]
    public string? CustomerName { get; set; }

    [JsonPropertyName("customer_phone")]
    public string? CustomerPhone { get; set; }

    [JsonPropertyName("notes")]
    public string? Notes { get; set; }
}
