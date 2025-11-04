<?php

namespace Modules\Gallery\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $guarded = [];

    public function getPathAttribute($value): string | null
    {
        return url($value);
    }
}
