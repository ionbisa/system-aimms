<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'schedule_date',
        'status',
        'description',
    ];

    protected $casts = [
        'schedule_date' => 'date',
    ];
}
