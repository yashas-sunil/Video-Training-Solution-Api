<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionPackage extends Model
{
    public function section()
    {
        return $this->belongsTo(Section::class);
    }
    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
