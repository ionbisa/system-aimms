<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemRequestApproval extends Model
{
    protected $fillable = [
        'item_request_id',
        'step_order',
        'stage_key',
        'stage_label',
        'role_name',
        'status',
        'note',
        'acted_by',
        'seen_at',
        'acted_at',
    ];

    protected $casts = [
        'seen_at' => 'datetime',
        'acted_at' => 'datetime',
    ];

    public function itemRequest(): BelongsTo
    {
        return $this->belongsTo(ItemRequest::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Pending',
        };
    }
}
