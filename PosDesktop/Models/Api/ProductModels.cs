using System.Text.Json.Serialization;

namespace PosDesktop.Models.Api;

public sealed class ProductListResponse
{
    [JsonPropertyName("items")]
    public List<ProductDto> Items { get; set; } = [];
}

public sealed class ProductEnvelope
{
    [JsonPropertyName("product")]
    public ProductDto Product { get; set; } = new();
}

public sealed class ProductDto
{
    [JsonPropertyName("id")]
    public int Id { get; set; }

    [JsonPropertyName("name")]
    public string Name { get; set; } = string.Empty;

    [JsonPropertyName("sku")]
    public string? Sku { get; set; }

    [JsonPropertyName("quantity")]
    public string? QuantityRaw { get; set; }

    [JsonPropertyName("selling_price")]
    public decimal SellingPrice { get; set; }

    [JsonPropertyName("status")]
    public string? Status { get; set; }

    [JsonIgnore]
    public decimal Quantity
    {
        get
        {
            if (decimal.TryParse(QuantityRaw, out var value))
            {
                return value;
            }

            return 0;
        }
    }
}
