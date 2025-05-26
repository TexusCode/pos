<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditItem extends Model
{
    protected $fillable = [
        'audit_id',
        'product_id',
        'old_quantity',
        'new_quantity',
        'difference',
    ];

    /**
     * Get the audit that owns the audit item.
     */
    public function audit()
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * Get the product that is associated with the audit item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
