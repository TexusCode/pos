<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'debt',
        'address',
    ];

    /**
     * Get the orders for the customer.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function debt()
    {
        return $this->hasMany(Debt::class);
    }
}
