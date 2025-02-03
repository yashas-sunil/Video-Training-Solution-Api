<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FreeResourcePackage extends Model
{
    protected $fillable = [
        'free_resource_packages',
    ];
    public function FreeResource()
    {
        return $this->belongsTo(FreeResource::class);
    }
}
