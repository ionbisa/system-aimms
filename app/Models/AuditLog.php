<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    protected $fillable = [
        'action',
        'description',
        'user_id',
    ];

    /**
     * Relasi ke user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Simpan log activity
     */
    public static function record(string $action, string $description): void
    {
        self::create([
            'action' => $action,
            'description' => $description,
            'user_id' => Auth::id(), // ✅ FIX AMAN & DIKENALI IDE
        ]);
    }
}
