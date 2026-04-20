<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asset extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'asset_code',
        'name',
        'location',
        'specification',
        'nopol',
        'type',
        'status',
        'pic',
        'photo',
    ];

    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class);
    }
}
