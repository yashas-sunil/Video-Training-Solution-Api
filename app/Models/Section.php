<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    public static $status = [
        0 => 'Disabled',
        1 => 'Enabled'
        ];

    const ENABLED = 1;
    const DISABLED = 0;

    public function sectionPackages()
    {
        return $this->belongsToMany(Package::class, 'section_packages', 'section_id', 'package_id');
    }
}
