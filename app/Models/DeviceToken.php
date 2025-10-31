<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'token',
        'device_type',
        'app_version',
        'device_info',
        'last_used_at',
        'is_active'
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    /**
     * Get the user that owns the device token.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
