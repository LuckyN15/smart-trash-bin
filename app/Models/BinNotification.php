<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BinNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'bin_id',
        'type',
        'title',
        'message',
        'level',
        'status',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function bin()
    {
        return $this->belongsTo(Bin::class);
    }
}
