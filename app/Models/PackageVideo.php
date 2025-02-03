<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageVideo extends BaseModel
{
    protected $guarded = ['id'];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

//    public function videos()
//    {
//        return $this->belongsTo(Video::class);
//    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

}
