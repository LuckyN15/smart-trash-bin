<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classification extends Model
{
    use HasFactory;

    protected $fillable = [
        'bin_compartment_id',
        'category',
        'detected_label',
        'confidence',
        'status',
        'model_version',
        'detected_at',
    ];

    protected $casts = [
        'confidence' => 'integer',
        'detected_at' => 'datetime',
    ];

    public function compartment()
    {
        return $this->belongsTo(BinCompartment::class, 'bin_compartment_id');
    }

    public function bin()
    {
        return $this->compartment->bin;
    }
}