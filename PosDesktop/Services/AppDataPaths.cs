namespace PosDesktop.Services;

public static class AppDataPaths
{
    public const string AppFolderName = "TexHubPosDesktop";

    public static string GetAppDataDirectory()
    {
        var localAppData = Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData);
        if (!string.IsNullOrWhiteSpace(localAppData))
        {
            var path = Path.Combine(localAppData, AppFolderName);
            Directory.CreateDirectory(path);
            return path;
        }

        var roaming = Environment.GetFolderPath(Environment.SpecialFolder.ApplicationData);
        if (!string.IsNullOrWhiteSpace(roaming))
        {
            var path = Path.Combine(roaming, AppFolderName);
            Directory.CreateDirectory(path);
            return path;
        }

        var tempPath = Path.Combine(Path.GetTempPath(), AppFolderName);
        Directory.CreateDirectory(tempPath);
        return tempPath;
    }

    public static string GetDatabasePath()
    {
        return Path.Combine(GetAppDataDirectory(), "local-pos.db");
    }

    public static string GetSessionPath()
    {
        return Path.Combine(GetAppDataDirectory(), "session.json");
    }
}
