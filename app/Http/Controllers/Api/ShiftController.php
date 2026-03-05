<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function current()
    {
        $shift = Shift::query()
            ->withCount('orders')
            ->latest()
            ->first();

        if (!$shift) {
            return response()->json([
                'shift' => null,
            ]);
        }

        return response()->json([
            'shift' => $this->serializeShift($shift),
        ]);
    }

    public function open(Request $request)
    {
        $payload = $request->validate([
            'initial_cash' => ['required', 'numeric', 'min:0'],
        ]);

        $alreadyOpen = Shift::query()->where('status', 'open')->exists();
        if ($alreadyOpen) {
            return response()->json([
                'message' => 'Shift is already open',
            ], 422);
        }

        $shift = Shift::query()->create([
            'initial_cash' => $payload['initial_cash'],
            'user_id' => $request->user()->id,
            'start_time' => now(),
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'Shift opened',
            'shift' => $this->serializeShift($shift),
        ], 201);
    }

    public function close(Request $request)
    {
        $payload = $request->validate([
            'final_cash' => ['required', 'numeric', 'min:0'],
        ]);

        $shift = Shift::query()
            ->with(['orders', 'debt', 'expences'])
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$shift) {
            return response()->json([
                'message' => 'Open shift not found',
            ], 404);
        }

        $debts = $shift->debt->where('type', 'Браль')->sum('total');
        $subTotal = $shift->orders->sum('sub_total_amount');
        $total = $shift->orders->sum('total_amount');
        $discounts = $shift->orders->sum('discount_amount');
        $expence = $shift->expences->sum('total');

        $shift->forceFill([
            'final_cash' => $payload['final_cash'],
            'sub_total' => $subTotal,
            'total' => $total - $expence,
            'expence' => $expence,
            'debts' => $debts,
            'discounts' => $discounts,
            'end_time' => now(),
            'status' => 'closed',
        ])->save();

        CartItem::truncate();
        Cart::truncate();

        return response()->json([
            'message' => 'Shift closed',
            'shift' => $this->serializeShift($shift->fresh()),
        ]);
    }

    private function serializeShift(Shift $shift): array
    {
        return [
            'id' => $shift->id,
            'status' => $shift->status,
            'start_time' => $shift->start_time,
            'end_time' => $shift->end_time,
            'initial_cash' => (float) $shift->initial_cash,
            'final_cash' => $shift->final_cash !== null ? (float) $shift->final_cash : null,
            'sub_total' => $shift->sub_total !== null ? (float) $shift->sub_total : null,
            'total' => $shift->total !== null ? (float) $shift->total : null,
            'expence' => $shift->expence !== null ? (float) $shift->expence : null,
            'discounts' => $shift->discounts !== null ? (float) $shift->discounts : null,
            'debts' => $shift->debts !== null ? (float) $shift->debts : null,
            'user_id' => $shift->user_id,
            'orders_count' => $shift->orders_count ?? null,
        ];
    }
}
