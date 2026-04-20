<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemRequest extends Model
{
    protected $fillable = [
        'request_number',
        'requested_at',
        'division',
        'requested_role',
        'overall_status',
        'current_step',
        'initial_note',
        'requested_by',
        'final_approved_at',
        'rejected_at',
        'expired_at',
        'ga_seen_at',
        'realization_status',
        'realization_note',
        'realized_by',
        'realized_at',
        'completed_at',
        'stock_deducted_at',
        'last_action_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'final_approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expired_at' => 'datetime',
        'ga_seen_at' => 'datetime',
        'realized_at' => 'datetime',
        'completed_at' => 'datetime',
        'stock_deducted_at' => 'datetime',
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

    public function items(): HasMany
    {
        return $this->hasMany(ItemRequestItem::class)->orderBy('line_number');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ItemRequestApproval::class)->orderBy('step_order');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ItemRequestNote::class)->latest();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->overall_status) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
            default => 'Pending',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->overall_status) {
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'expired' => 'bg-secondary',
            default => 'bg-warning text-dark',
        };
    }

    public function getCurrentStepLabelAttribute(): string
    {
        return match ($this->current_step) {
            'waiting_production_head' => 'Menunggu Kepala Produksi',
            'waiting_operational_manager' => 'Menunggu Manager Operasional',
            'waiting_ga_realization' => 'Menunggu Realisasi Admin GA',
            'completed' => 'Selesai Didistribusikan',
            'rejected' => 'Permintaan Ditolak',
            'expired' => 'Permintaan Expired',
            default => 'Proses Permintaan',
        };
    }

    public function getRealizationLabelAttribute(): string
    {
        return match ($this->realization_status) {
            'ready_for_distribution' => 'Barang Siap Didistribusikan',
            'distributed' => 'Barang Sudah Diberikan',
            'purchase_required' => 'Perlu Pembelian Terlebih Dahulu',
            default => 'Belum Ada Realisasi',
        };
    }
}
