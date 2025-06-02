<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnOrder extends Model
{
    protected $fillable = [
        'shift_id',
        'total',
        'discount',
    ];

    public function shift()
    {
        return $this->hasMany(Shift::class);
    }
    public function return_order_items()
    {
        return $this->hasMany(ReturnOrderItem::class);
    }
}
