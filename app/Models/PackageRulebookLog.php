<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackageRulebookLog extends Model
{
    protected $guarded = ['id'];

    protected $primaryKey = 'id';

    public $timestamps = false;

    
    public function package()
    {
        return $this->belongsTo(Package::class,'package_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

}
