<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_name',
        'trip_date',
        'bins_served',
        'weight_kg',
        'duration_minutes',
        'on_time',
        'completed',
    ];

    protected $casts = [
        'trip_date' => 'date',
        'bins_served' => 'integer',
        'weight_kg' => 'decimal:2',
        'duration_minutes' => 'integer',
        'on_time' => 'boolean',
        'completed' => 'boolean',
    ];
}
