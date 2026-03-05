using PosDesktop.Models.Api;

namespace PosDesktop.Models.Local;

public static class LocalCartStates
{
    public const string Active = "active";
    public const string Deleted = "deleted";
    public const string CheckoutPending = "checkout_pending";
}

public sealed class LocalCartRecord
{
    public int ClientId { get; set; }
    public int? ServerId { get; set; }
    public string UserId { get; set; } = string.Empty;
    public CartDto Cart { get; set; } = new();
    public string State { get; set; } = LocalCartStates.Active;
    public bool IsDirty { get; set; }
    public CheckoutRequest? PendingCheckout { get; set; }
    public DateTime UpdatedAtUtc { get; set; } = DateTime.UtcNow;
}
