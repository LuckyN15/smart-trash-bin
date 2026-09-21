<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BinCompartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'bin_id',
        'category',
        'capacity_percent',
        'status',
        'last_updated_at',
    ];

    protected $casts = [
        'capacity_percent' => 'integer',
        'last_updated_at' => 'datetime',
    ];

    public function bin()
    {
        return $this->belongsTo(Bin::class);
    }

    public function classifications()
    {
        return $this->hasMany(Classification::class);
    }
}
