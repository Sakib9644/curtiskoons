<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class SpikeMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'provider_slug', 'date', 'hrv', 'rhr', 'sleep_hours', 'steps'
    ];
}
