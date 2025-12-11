<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiomarkerGenetic extends Model
{
    protected $guarded = [];

    public function biomarker()
    {
        return $this->belongsTo(Biomarker::class);
    }
}
