<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'initial_cash',
        'final_cash',
        'sub_total',
        'user_id',
        'status',
        'discounts',
        'debts',
    ];

    /**
     * Get the orders for the shift.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the user that opened the shift.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
