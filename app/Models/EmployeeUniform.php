<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class EmployeeUniform extends Model
{
    protected $fillable = [
        'pickup_date',
        'expiry_date',
        'employee_name',
        'employee_code',
        'department',
        'shirt_size',
        'quantity_given',
        'condition',
        'notes',
        'photo',
    ];

    protected $casts = [
        'pickup_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function getRemainingDaysAttribute(): int
    {
        $today = Carbon::today();

        if (! $this->expiry_date) {
            return 0;
        }

        return max($today->diffInDays($this->expiry_date, false), 0);
    }

    public function getStatusLabelAttribute(): string
    {
        if (! $this->expiry_date) {
            return 'Habis';
        }

        return $this->expiry_date->isPast() && ! $this->expiry_date->isToday()
            ? 'Habis'
            : 'Aktif';
    }
}
