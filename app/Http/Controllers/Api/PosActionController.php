<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Buy;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\Expence;
use App\Models\Product;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosActionController extends Controller
{
    public function addExpense(Request $request)
    {
        $payload = $request->validate([
            'total' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:2000'],
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

        $expense = Expence::query()->create([
            'shift_id' => $shift->id,
            'total' => (float) $payload['total'],
            'description' => $payload['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Expense added',
            'expense' => [
                'id' => $expense->id,
                'shift_id' => $expense->shift_id,
                'total' => (float) $expense->total,
                'description' => $expense->description,
                'created_at' => $expense->created_at,
            ],
        ], 201);
    }

    public function findCustomerByPhone(string $phone)
    {
        $customer = Customer::query()
            ->where('phone', trim($phone))
            ->first();

        return response()->json([
            'customer' => $customer ? $this->serializeCustomer($customer) : null,
        ]);
    }

    public function payDebt(Request $request)
    {
        $payload = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'total' => ['required', 'numeric', 'min:0.01'],
            'customer_name' => ['nullable', 'string', 'max:150'],
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

        $amount = (float) $payload['total'];
        $phone = trim($payload['phone']);
        $customerName = isset($payload['customer_name']) ? trim((string) $payload['customer_name']) : null;

        $customer = null;

        DB::transaction(function () use ($shift, $amount, $phone, $customerName, &$customer) {
            $customer = Customer::query()
                ->where('phone', $phone)
                ->first();

            if (!$customer) {
                $customer = Customer::query()->create([
                    'name' => $customerName ?: now()->toDateTimeString(),
                    'phone' => $phone,
                    'debt' => 0,
                ]);
            }

            Debt::query()->create([
                'customer_id' => $customer->id,
                'order_id' => null,
                'shift_id' => $shift->id,
                'total' => $amount,
                'type' => 'Вернуль',
            ]);

            $currentDebt = (float) ($customer->debt ?? 0);
            $customer->debt = max(0, $currentDebt - $amount);
            $customer->save();
        });

        return response()->json([
            'message' => 'Debt payment recorded',
            'customer' => $this->serializeCustomer($customer),
            'payment' => [
                'amount' => $amount,
                'type' => 'Вернуль',
                'shift_id' => $shift->id,
            ],
        ], 201);
    }

    public function upsertProductStock(Request $request)
    {
        $payload = $request->validate([
            'sku' => ['required', 'string', 'max:120'],
            'quantity' => ['required', 'integer', 'min:1'],
            'name' => ['nullable', 'string', 'max:255'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:active,inactive,draft'],
        ]);

        $sku = trim((string) $payload['sku']);
        $qty = (int) $payload['quantity'];

        $created = false;
        $product = Product::query()->where('sku', $sku)->first();

        if (!$product && (empty($payload['name']) || !array_key_exists('selling_price', $payload))) {
            return response()->json([
                'message' => 'name and selling_price are required for new product',
            ], 422);
        }

        DB::transaction(function () use ($payload, $sku, $qty, &$product, &$created) {
            if ($product) {
                $currentQty = (float) ($product->quantity ?? 0);
                $product->quantity = (string) ($currentQty + $qty);

                if (array_key_exists('name', $payload) && !empty(trim((string) $payload['name']))) {
                    $product->name = trim((string) $payload['name']);
                }

                if (array_key_exists('selling_price', $payload) && $payload['selling_price'] !== null) {
                    $product->selling_price = (float) $payload['selling_price'];
                }

                if (array_key_exists('status', $payload) && !empty($payload['status'])) {
                    $product->status = $payload['status'];
                }

                $product->save();
            } else {
                $product = Product::query()->create([
                    'sku' => $sku,
                    'name' => trim((string) $payload['name']),
                    'quantity' => (string) $qty,
                    'selling_price' => (float) $payload['selling_price'],
                    'status' => $payload['status'] ?? 'active',
                ]);

                $created = true;
            }

            Buy::query()->create([
                'product_id' => $product->id,
                'quantity' => $qty,
            ]);
        });

        return response()->json([
            'message' => $created ? 'Product created and stock added' : 'Product stock updated',
            'created' => $created,
            'product' => $product->fresh(),
        ], $created ? 201 : 200);
    }

    private function serializeCustomer(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'debt' => (float) ($customer->debt ?? 0),
        ];
    }
}
