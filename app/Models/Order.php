<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'order_number',
        'total_amount',
        'discount_amount', // Добавляем новое поле
        'status',
        'payment_method',  // Добавляем новое поле
        'shift_id',        // Добавляем новое поле
        'payment_status',  // Добавляем новое поле
        'notes',
    ];

    /**
     * Get the customer that owns the order.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the order items for the order.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the shift that the order belongs to.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
