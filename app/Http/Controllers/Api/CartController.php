<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $carts = Cart::query()
            ->where('user_id', (string) $request->user()->id)
            ->with('items.product')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'items' => $carts->map(fn (Cart $cart) => $this->serializeCart($cart))->values(),
        ]);
    }

    public function store(Request $request)
    {
        $cart = Cart::query()->create([
            'user_id' => (string) $request->user()->id,
            'discount' => 0,
            'subtotal' => 0,
            'total' => 0,
        ]);

        return response()->json([
            'message' => 'Cart created',
            'cart' => $this->serializeCart($cart),
        ], 201);
    }

    public function show(Request $request, Cart $cart)
    {
        if ($error = $this->denyForeignCart($request, $cart)) {
            return $error;
        }

        $cart->load('items.product');

        return response()->json([
            'cart' => $this->serializeCart($cart),
        ]);
    }

    public function destroy(Request $request, Cart $cart)
    {
        if ($error = $this->denyForeignCart($request, $cart)) {
            return $error;
        }

        CartItem::query()->where('cart_id', $cart->id)->delete();
        $cart->delete();

        return response()->json([
            'message' => 'Cart removed',
        ]);
    }

    public function addItem(Request $request, Cart $cart)
    {
        if ($error = $this->denyForeignCart($request, $cart)) {
            return $error;
        }

        $payload = $request->validate([
            'product_id' => ['nullable', 'integer'],
            'sku' => ['nullable', 'string'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        if (empty($payload['product_id']) && empty($payload['sku'])) {
            return response()->json([
                'message' => 'product_id or sku is required',
            ], 422);
        }

        $product = null;
        if (!empty($payload['product_id'])) {
            $product = Product::query()->find($payload['product_id']);
        } elseif (!empty($payload['sku'])) {
            $product = Product::query()->where('sku', $payload['sku'])->first();
        }

        if (!$product) {
            return response()->json([
                'message' => 'Product not found',
            ], 404);
        }

        $quantity = (int) ($payload['quantity'] ?? 1);

        $item = CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if ($item) {
            $item->forceFill([
                'quantity' => $item->quantity + $quantity,
            ])->save();
        } else {
            CartItem::query()->create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'discount' => 0,
            ]);
        }

        $this->persistCartTotals($cart);

        return response()->json([
            'message' => 'Item added',
            'cart' => $this->serializeCart($cart->fresh('items.product')),
        ]);
    }

    public function updateItem(Request $request, Cart $cart, CartItem $item)
    {
        if ($error = $this->denyForeignCart($request, $cart)) {
            return $error;
        }

        if ((int) $item->cart_id !== (int) $cart->id) {
            return response()->json([
                'message' => 'Item does not belong to cart',
            ], 422);
        }

        $payload = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (array_key_exists('quantity', $payload)) {
            $item->quantity = (int) $payload['quantity'];
        }

        if (array_key_exists('discount', $payload)) {
            $item->discount = (float) $payload['discount'];
        }

        $item->save();
        $this->persistCartTotals($cart);

        return response()->json([
            'message' => 'Item updated',
            'cart' => $this->serializeCart($cart->fresh('items.product')),
        ]);
    }

    public function removeItem(Request $request, Cart $cart, CartItem $item)
    {
        if ($error = $this->denyForeignCart($request, $cart)) {
            return $error;
        }

        if ((int) $item->cart_id !== (int) $cart->id) {
            return response()->json([
                'message' => 'Item does not belong to cart',
            ], 422);
        }

        $item->delete();
        $this->persistCartTotals($cart);

        return response()->json([
            'message' => 'Item removed',
            'cart' => $this->serializeCart($cart->fresh('items.product')),
        ]);
    }

    public function applyDiscount(Request $request, Cart $cart)
    {
        if ($error = $this->denyForeignCart($request, $cart)) {
            return $error;
        }

        $payload = $request->validate([
            'type' => ['required', 'in:fixed,percent'],
            'value' => ['required', 'numeric', 'min:0'],
        ]);

        $totals = $this->computeCartTotals($cart);

        $discount = $payload['type'] === 'percent'
            ? ($totals['subtotal_raw'] * (float) $payload['value']) / 100
            : (float) $payload['value'];

        $cart->forceFill([
            'discount' => $discount,
        ])->save();

        $this->persistCartTotals($cart);

        return response()->json([
            'message' => 'Discount applied',
            'cart' => $this->serializeCart($cart->fresh('items.product')),
        ]);
    }

    public function checkout(Request $request, Cart $cart)
    {
        if ($error = $this->denyForeignCart($request, $cart)) {
            return $error;
        }

        $payload = $request->validate([
            'payment_method' => ['nullable', 'string', 'max:100'],
            'payment_status' => ['nullable', 'in:paid,debt'],
            'cash' => ['nullable', 'numeric', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $shift = Shift::query()
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$shift) {
            return response()->json([
                'message' => 'Open shift not found',
            ], 422);
        }

        $cart->load('items.product');
        if ($cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty',
            ], 422);
        }

        $totals = $this->computeCartTotals($cart);
        $paymentMethod = $payload['payment_method'] ?? 'Наличными';
        $paymentStatus = $payload['payment_status']
            ?? ($paymentMethod === 'В долг' ? 'debt' : 'paid');

        $cash = (float) ($payload['cash'] ?? 0);
        $debtAmount = max(0, $totals['total'] - $cash);

        if ($paymentStatus === 'debt' && !$payload['customer_phone']) {
            return response()->json([
                'message' => 'customer_phone is required for debt payment',
            ], 422);
        }

        $order = null;
        $newCart = null;

        DB::transaction(function () use (
            $request,
            $cart,
            $shift,
            $totals,
            $paymentMethod,
            $paymentStatus,
            $payload,
            $debtAmount,
            &$order,
            &$newCart
        ) {
            $customer = null;

            if (!empty($payload['customer_phone'])) {
                $customer = Customer::query()
                    ->where('phone', $payload['customer_phone'])
                    ->first();
            }

            if (!$customer && (!empty($payload['customer_phone']) || !empty($payload['customer_name']))) {
                $customer = Customer::query()->create([
                    'name' => $payload['customer_name'] ?? now()->toDateTimeString(),
                    'phone' => $payload['customer_phone'] ?? uniqid('guest-', true),
                    'debt' => 0,
                ]);
            }

            if ($customer && $paymentStatus === 'debt' && $debtAmount > 0) {
                $customer->debt = (float) $customer->debt + $debtAmount;
                $customer->save();
            }

            $order = Order::query()->create([
                'customer_id' => $customer?->id,
                'total_amount' => $totals['total'],
                'sub_total_amount' => $totals['subtotal_raw'],
                'discount_amount' => $totals['total_discount'],
                'payment_method' => $paymentMethod,
                'shift_id' => $shift->id,
                'payment_status' => $paymentStatus,
                'notes' => $payload['notes'] ?? null,
            ]);

            foreach ($cart->items as $item) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->selling_price,
                    'discount' => (float) ($item->discount ?? 0),
                    'subtotal' => $item->quantity * (float) $item->product->selling_price,
                ]);

                $product = Product::query()->find($item->product_id);
                if ($product) {
                    $product->quantity = (float) $product->quantity - (float) $item->quantity;
                    $product->save();
                }
            }

            if ($paymentStatus === 'debt' && $debtAmount > 0 && $customer) {
                Debt::query()->create([
                    'customer_id' => $customer->id,
                    'order_id' => $order->id,
                    'shift_id' => $shift->id,
                    'total' => $debtAmount,
                    'type' => 'Браль',
                ]);
            }

            CartItem::query()->where('cart_id', $cart->id)->delete();
            $cart->delete();

            $newCart = Cart::query()->create([
                'user_id' => (string) $request->user()->id,
                'discount' => 0,
                'subtotal' => 0,
                'total' => 0,
            ]);
        });

        return response()->json([
            'message' => 'Checkout complete',
            'order' => $order,
            'new_cart' => $newCart,
            'totals' => [
                'subtotal' => $totals['subtotal_raw'],
                'discount' => $totals['total_discount'],
                'total' => $totals['total'],
            ],
        ]);
    }

    private function denyForeignCart(Request $request, Cart $cart): ?JsonResponse
    {
        if ((string) $cart->user_id !== (string) $request->user()->id) {
            return response()->json([
                'message' => 'Cart not found',
            ], 404);
        }

        return null;
    }

    private function persistCartTotals(Cart $cart): void
    {
        $totals = $this->computeCartTotals($cart->fresh('items.product'));

        $cart->forceFill([
            'subtotal' => $totals['subtotal_raw'],
            'total' => $totals['total'],
        ])->save();
    }

    private function computeCartTotals(Cart $cart): array
    {
        $cart->loadMissing('items.product');

        $subtotalRaw = 0.0;
        $itemDiscount = 0.0;

        foreach ($cart->items as $item) {
            $price = (float) ($item->product->selling_price ?? 0);
            $subtotalRaw += $price * (int) $item->quantity;
            $itemDiscount += (float) ($item->discount ?? 0);
        }

        $cartDiscount = (float) ($cart->discount ?? 0);
        $totalDiscount = $itemDiscount + $cartDiscount;
        $total = max(0, $subtotalRaw - $totalDiscount);

        return [
            'subtotal_raw' => round($subtotalRaw, 2),
            'item_discount' => round($itemDiscount, 2),
            'cart_discount' => round($cartDiscount, 2),
            'total_discount' => round($totalDiscount, 2),
            'total' => round($total, 2),
        ];
    }

    private function serializeCart(Cart $cart): array
    {
        $cart->loadMissing('items.product');
        $totals = $this->computeCartTotals($cart);

        return [
            'id' => $cart->id,
            'user_id' => $cart->user_id,
            'discount' => (float) ($cart->discount ?? 0),
            'subtotal' => (float) ($cart->subtotal ?? 0),
            'total' => (float) ($cart->total ?? 0),
            'totals_live' => $totals,
            'items' => $cart->items->map(function (CartItem $item) {
                $price = (float) ($item->product->selling_price ?? 0);

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? null,
                    'product_sku' => $item->product->sku ?? null,
                    'price' => $price,
                    'quantity' => (int) $item->quantity,
                    'discount' => (float) ($item->discount ?? 0),
                    'line_subtotal' => round($price * (int) $item->quantity, 2),
                ];
            })->values(),
        ];
    }
}
