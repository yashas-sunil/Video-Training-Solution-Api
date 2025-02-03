<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CustomTestimonial extends BaseModel
{
    const UNPUBLISHED = 1;
    const PUBLISHED = 2;


    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeOfPublished($query)
    {
        return $query->where('publish', self::PUBLISHED);
    }

    public function getImageAttribute($value) {
        if (! $value) {
            return null;
        }

        return env('IMAGE_URL').'/custom_testimonials/'.$value;
    }


    public static function getAll()
    {
        /** @var CustomTestimonial|Builder $query */
        $query = CustomTestimonial::query();

        $query->ofPublished();

        return $query->get();
    }
}
