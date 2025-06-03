<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expence extends Model
{
    protected $fillable = [
        'shift_id',
        'total',
        'description',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
