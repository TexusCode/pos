using System.Text.Json.Serialization;

namespace PosDesktop.Models;

public sealed class AppConfig
{
    [JsonPropertyName("api_base_url")]
    public string ApiBaseUrl { get; set; } = "https://topcars.tj";
}
