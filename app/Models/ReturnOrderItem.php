<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnOrderItem extends Model
{
    protected $fillable = [
        'return_order_id',
        'product_id',
        'quantity',
        'price',
        'discount',
        'subtotal',
    ];

    public function return_order()
    {
        return $this->belongsTo(ReturnOrder::class, 'return_order_id');
    }
}
