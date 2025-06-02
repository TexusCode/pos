<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Debt extends Model
{
    protected $fillable = [
        'customer_id',
        'order_id',
        'shift_id',
        'total',
        'type',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
