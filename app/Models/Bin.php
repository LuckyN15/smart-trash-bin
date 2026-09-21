<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bin extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'name',
        'location',
        'area',
        'camera_ip',
        'battery_level',
        'status',
        'pending_servo_command',
        'last_reported_at',
    ];

    protected $casts = [
        'battery_level' => 'integer',
        'pending_servo_command' => 'integer',
        'last_reported_at' => 'datetime',
    ];

    public function compartments()
    {
        return $this->hasMany(BinCompartment::class);
    }

    public function notifications()
    {
        return $this->hasMany(BinNotification::class);
    }

    /**
     * Get overall capacity percentage (average of all compartments)
     */
    public function getOverallCapacityAttribute()
    {
        $compartments = $this->compartments;
        if ($compartments->isEmpty()) {
            return 0;
        }
        return round($compartments->avg('capacity_percent'));
    }
}
