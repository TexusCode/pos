using System.Text.Json;
using PosDesktop.Models;

namespace PosDesktop.Services;

public static class ConfigLoader
{
    public static AppConfig Load()
    {
        var baseDirectory = AppContext.BaseDirectory;
        var appSettingsPath = Path.Combine(baseDirectory, "appsettings.json");

        if (!File.Exists(appSettingsPath))
        {
            appSettingsPath = Path.Combine(Environment.CurrentDirectory, "appsettings.json");
        }

        if (!File.Exists(appSettingsPath))
        {
            return new AppConfig();
        }

        var json = File.ReadAllText(appSettingsPath);
        var config = JsonSerializer.Deserialize<AppConfig>(json, new JsonSerializerOptions
        {
            PropertyNameCaseInsensitive = true,
        });

        return config ?? new AppConfig();
    }
}
