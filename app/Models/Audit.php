<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    protected $fillable = [
        'audit_date',
        'name',
        'user_id',
        'notes',
        'status',
        'total_negative_items_count',
        'total_negative_difference_sum',
        'total_negative_value_sum',
    ];

    /**
     * Get the user that created the audit.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the audit items for the audit.
     */
    public function auditItems()
    {
        return $this->hasMany(AuditItem::class);
    }
}
