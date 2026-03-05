using System.Text.Json;

namespace PosDesktop.Services;

public static class SessionStore
{
    private static readonly string SessionPath = Path.Combine(
        Environment.GetFolderPath(Environment.SpecialFolder.ApplicationData),
        "TexHubPosDesktop",
        "session.json");

    public static string? LoadToken()
    {
        if (!File.Exists(SessionPath))
        {
            return null;
        }

        var json = File.ReadAllText(SessionPath);
        var payload = JsonSerializer.Deserialize<SessionPayload>(json);
        return payload?.AccessToken;
    }

    public static void SaveToken(string accessToken)
    {
        var directory = Path.GetDirectoryName(SessionPath);
        if (!string.IsNullOrWhiteSpace(directory))
        {
            Directory.CreateDirectory(directory);
        }

        var payload = new SessionPayload
        {
            AccessToken = accessToken,
        };

        File.WriteAllText(SessionPath, JsonSerializer.Serialize(payload));
    }

    public static void Clear()
    {
        if (File.Exists(SessionPath))
        {
            File.Delete(SessionPath);
        }
    }

    private sealed class SessionPayload
    {
        public string AccessToken { get; set; } = string.Empty;
    }
}
