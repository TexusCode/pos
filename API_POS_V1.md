# POS API v1

Base URL: `/api/v1`

## Authentication

### `POST /auth/login`
Request:

```json
{
  "phone": "+992900000000",
  "password": "secret",
  "device_name": "cashbox-pc-1",
  "token_ttl_hours": 720
}
```

Response:

```json
{
  "token_type": "Bearer",
  "access_token": "plain-token",
  "expires_at": "2026-03-30T10:00:00.000000Z",
  "user": {
    "id": 1,
    "name": "Cashier",
    "phone": "+992900000000",
    "role": "pos"
  }
}
```

Use `Authorization: Bearer <access_token>` for all protected endpoints.

### `GET /auth/me`
Returns current user.

### `POST /auth/logout`
Optional body:

```json
{
  "all_devices": true
}
```

## Shift

### `GET /shift/current`
Returns latest shift or `null`.

### `POST /shift/open`

```json
{
  "initial_cash": 100
}
```

### `POST /shift/close`

```json
{
  "final_cash": 2500
}
```

## Products

### `GET /products?search=esse&per_page=50`
Filters by `name` and `sku`.

### `GET /products/{id}`

### `GET /products/by-sku/{sku}`

## Carts

### `GET /carts`
Returns only carts of current user.

### `POST /carts`
Creates empty cart.

### `GET /carts/{cartId}`

### `DELETE /carts/{cartId}`

### `POST /carts/{cartId}/items`

```json
{
  "sku": "8801116007257",
  "quantity": 1
}
```

or

```json
{
  "product_id": 12,
  "quantity": 2
}
```

### `PATCH /carts/{cartId}/items/{itemId}`

```json
{
  "quantity": 3,
  "discount": 5
}
```

### `DELETE /carts/{cartId}/items/{itemId}`

### `POST /carts/{cartId}/discount`

```json
{
  "type": "fixed",
  "value": 10
}
```

or percent:

```json
{
  "type": "percent",
  "value": 5
}
```

### `POST /carts/{cartId}/checkout`

```json
{
  "payment_method": "Наличными",
  "payment_status": "paid",
  "cash": 0,
  "customer_name": "Client",
  "customer_phone": "+992900000001",
  "notes": "desktop checkout"
}
```

If `payment_status = debt`, `customer_phone` is required.

## Setup

1. Run migrations:

```bash
php artisan migrate
```

2. Call `POST /api/v1/auth/login`, save token in C# app, send it as Bearer header.
