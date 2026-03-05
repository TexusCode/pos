# TexHub POS Desktop (C# / Avalonia)

Desktop POS client with the same flow as web POS:

1. Login
2. Shift opening screen (if no open shift)
3. POS cashier screen

## API

This app uses your Laravel API endpoints under `/api/v1`.

Configure server URL in `appsettings.json`:

```json
{
  "api_base_url": "https://topcars.tj"
}
```

## Run

```bash
dotnet restore
dotnet run --project PosDesktop.csproj
```

## Current implemented features

- Login / Logout (token auth)
- Restore session from local app data
- Detect current shift
- Open / Close shift
- Product list + search
- Add to cart (by product or barcode)
- Multiple carts (list, select, create)
- Cart item quantity +/-
- Item remove
- Cart discount (fixed/percent)
- Checkout (cash/card/debt)
- Cart reset
