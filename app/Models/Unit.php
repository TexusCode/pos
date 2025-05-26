<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = [
        'name',
        'symbol',
    ];

    /**
     * Get the products that use this unit.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
