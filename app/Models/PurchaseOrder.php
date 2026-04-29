<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number',
        'transaction_date',
        'transaction_type',
        'division',
        'category',
        'description',
        'vendor',
        'qty',
        'unit',
        'unit_price',
        'total_price',
        'actual_total_price',
        'status_label',
        'photo',
        'requested_by',
        'requested_role',
        'overall_status',
        'current_step',
        'initial_note',
        'final_approved_at',
        'rejected_at',
        'expired_at',
        'finance_seen_at',
        'realization_status',
        'realization_note',
        'realized_by',
        'realized_at',
        'ga_seen_at',
        'receipt_note',
        'receipt_file',
        'completed_by',
        'completed_at',
        'last_action_at',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'actual_total_price' => 'decimal:2',
        'final_approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expired_at' => 'datetime',
        'finance_seen_at' => 'datetime',
        'realized_at' => 'datetime',
        'ga_seen_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_action_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function realizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'realized_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class)->orderBy('line_number');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PurchaseOrderApproval::class)->orderBy('step_order');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(PurchaseOrderNote::class)->latest();
    }

    public function getDisplayStatusAttribute(): string
    {
        if (! empty($this->overall_status)) {
            return match ($this->overall_status) {
                'approved' => 'Approved',
                'done' => 'Done',
                'rejected' => 'Rejected',
                default => 'Pending',
            };
        }

        if (! empty($this->status_label)) {
            return $this->status_label;
        }

        return match ($this->status) {
            'Approved' => 'Selesai',
            'Rejected' => 'Pending',
            default => 'Proses',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->display_status) {
            'Approved', 'Selesai' => 'bg-success',
            'Done' => 'bg-primary',
            'Rejected' => 'bg-danger',
            default => 'bg-warning text-dark',
        };
    }

    public function getCurrentStepLabelAttribute(): string
    {
        return match ($this->current_step) {
            'waiting_operational_manager' => 'Menunggu Manager Operasional',
            'waiting_director' => 'Menunggu Direktur Operasional',
            'waiting_finance_realization' => in_array($this->realization_status, ['pending', 'fund_ready'], true)
                ? 'Menunggu Tindak Lanjut Manager Finance'
                : 'Menunggu Realisasi Manager Finance',
            'waiting_ga_completion' => 'Menunggu Penyelesaian Admin GA',
            'completed' => 'Done',
            'rejected' => 'Permintaan Ditolak',
            'expired' => 'Menunggu Follow Up Ulang',
            default => 'Proses Purchase Order',
        };
    }

    public function getRealizationLabelAttribute(): string
    {
        return match ($this->realization_status) {
            'pending' => 'Pending di Manager Finance',
            'rejected' => 'Ditolak Manager Finance',
            'fund_ready' => 'Uang Siap Dikeluarkan',
            'fund_disbursed' => 'Uang Sudah Diberikan',
            'done' => 'Done',
            default => 'Belum Ada Realisasi',
        };
    }

    public function getReceiptFileUrlAttribute(): ?string
    {
        $path = $this->receipt_file ?: $this->photo;

        if (! $path) {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return url('/media/' . ltrim($path, '/'));
    }

    public function getGaCompletionNoteAttribute(): ?string
    {
        if (! empty($this->receipt_note)) {
            return $this->receipt_note;
        }

        $notes = $this->relationLoaded('notes')
            ? $this->notes
            : $this->notes()->latest()->get();

        return optional(
            $notes->first(fn ($note) => $note->type === 'completion')
        )->note;
    }

    public function getEffectiveTotalPriceAttribute(): float
    {
        $actualTotalPrice = $this->actual_total_price;

        if (! is_null($actualTotalPrice) && $actualTotalPrice !== '') {
            return (float) $actualTotalPrice;
        }

        return (float) $this->total_price;
    }

    public function getPriceVarianceAttribute(): float
    {
        return $this->effective_total_price - (float) $this->total_price;
    }
}
