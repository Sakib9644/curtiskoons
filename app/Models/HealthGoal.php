<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'goal',
        'methods',
        'timeline_years',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
