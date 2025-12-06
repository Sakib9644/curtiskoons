<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabReport extends Model
{
    use HasFactory;

    protected $guarded = [];

  protected $casts = [
        'sections' => 'array',
        'patient_information' => 'array',
        'lab_information' => 'array',
    ];
    public function patient() {
        return $this->belongsTo(User::class);
    }
}
