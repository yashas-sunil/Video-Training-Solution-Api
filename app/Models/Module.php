<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    public function packageVideos()
    {
        return $this->hasMany(PackageVideo::class);
    }
}
