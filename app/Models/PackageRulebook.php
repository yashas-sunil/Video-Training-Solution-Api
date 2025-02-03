<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackageRulebook extends Model
{
    use SoftDeletes;

    protected $table = 'package_rulebook';

    protected $guarded = ['id'];

    protected $primaryKey = 'id';
    
    public function package()
    {
        return $this->belongsTo(Package::class,'package_id');
    }

}
