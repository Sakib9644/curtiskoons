<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Biomarker extends Model
{
    protected $guarded = [];

    /**
     * Numeric ranges for this biomarker.
     */
    public function ranges()
    {
        return $this->hasMany(BiomarkerRange::class);
    }

    /**
     * Genetic variants (APOE, MTHFR, etc.)
     */
    public function genetics()
    {
        return $this->hasMany(BiomarkerGenetic::class);
    }
}
