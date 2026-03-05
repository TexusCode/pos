using System.Text.Json.Serialization;

namespace PosDesktop.Models.Api;

public sealed class ShiftCurrentResponse
{
    [JsonPropertyName("shift")]
    public ShiftDto? Shift { get; set; }
}

public sealed class ShiftActionResponse
{
    [JsonPropertyName("message")]
    public string Message { get; set; } = string.Empty;

    [JsonPropertyName("shift")]
    public ShiftDto Shift { get; set; } = new();
}

public sealed class ShiftDto
{
    [JsonPropertyName("id")]
    public int Id { get; set; }

    [JsonPropertyName("status")]
    public string Status { get; set; } = string.Empty;

    [JsonPropertyName("start_time")]
    public string? StartTime { get; set; }

    [JsonPropertyName("end_time")]
    public string? EndTime { get; set; }

    [JsonPropertyName("initial_cash")]
    public decimal InitialCash { get; set; }

    [JsonPropertyName("final_cash")]
    public decimal? FinalCash { get; set; }

    [JsonPropertyName("sub_total")]
    public decimal? SubTotal { get; set; }

    [JsonPropertyName("total")]
    public decimal? Total { get; set; }

    [JsonPropertyName("expence")]
    public decimal? Expence { get; set; }

    [JsonPropertyName("discounts")]
    public decimal? Discounts { get; set; }

    [JsonPropertyName("debts")]
    public decimal? Debts { get; set; }

    [JsonPropertyName("user_id")]
    public int? UserId { get; set; }

    [JsonPropertyName("orders_count")]
    public int? OrdersCount { get; set; }
}
