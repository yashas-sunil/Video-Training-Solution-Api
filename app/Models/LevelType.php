<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LevelType extends Model
{
    public function packagetypes()
    {
        return $this->belongsTo(PackageType::class);
    }
}
