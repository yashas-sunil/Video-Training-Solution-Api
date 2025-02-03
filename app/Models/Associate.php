<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Associate extends BaseModel
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function getImageAttribute($value) {
        if (! $value) {
            return null;
        }

        return env('IMAGE_URL').'/associates/images/'.$value;
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
