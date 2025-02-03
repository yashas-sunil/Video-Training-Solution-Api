<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectPackage extends Model
{
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function packageVideos()
    {
        return $this->hasMany(PackageVideo::class, 'package_id', 'chapter_package_id');
    }
}
