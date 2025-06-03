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
        // Поле shift_id в ReturnOrder ссылается на id в Shift
        // Предполагается, что `shift_id` это foreign key, а `id` это local key
        return $this->belongsTo(Shift::class, 'shift_id', 'id');
    }

    public function return_order_items()
    {
        return $this->hasMany(ReturnOrderItem::class, 'return_order_id', 'id');
    }
}
