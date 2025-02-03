<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechSupport extends Model
{
    protected $table = 'tech_support';

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->updated_at = null;
        });
    }
}