<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderNoteRead extends Model
{
    protected $fillable = [
        'purchase_order_note_id',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function note(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderNote::class, 'purchase_order_note_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
