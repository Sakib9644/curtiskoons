<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabReport extends Model
{
    use HasFactory;

    protected $fillable = ['user_id','file_path','extracted_data','status'];

    protected $casts = [
        'extracted_data' => 'array',
    ];

    public function patient() {
        return $this->belongsTo(User::class);
    }
}
