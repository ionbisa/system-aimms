<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemRequestItem extends Model
{
    protected $fillable = [
        'item_request_id',
        'line_number',
        'item_name',
        'qty',
        'unit',
        'description',
        'stock_id',
        'distributed_qty',
        'procurement_type',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'distributed_qty' => 'decimal:2',
    ];

    public function itemRequest(): BelongsTo
    {
        return $this->belongsTo(ItemRequest::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function getProcurementTypeLabelAttribute(): string
    {
        return match ($this->procurement_type) {
            'purchase_request' => 'Permintaan Pembelian',
            default => 'Distribusi Stok',
        };
    }
}
