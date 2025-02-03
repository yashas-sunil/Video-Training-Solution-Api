<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Testimonial extends BaseModel
{
    protected $guarded = ['id'];

    const UNPUBLISHED = 1;
    const PUBLISHED = 2;



    public function student()
    {
        return $this->belongsTo('App\Models\Student');
    }

        /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeOfProfessor($query)
    {
        return $this->where('professor_id', Auth::id())->with('student');
    }


    public function getImageAttribute($value) {
        if (! $value) {
            return null;
        }

        return env('IMAGE_URL').'/testimonials/'.$value;
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeOfPublished($query)
    {
        return $query->where('publish', self::PUBLISHED);
    }

    public static function getAll()
    {
        /** @var Testimonial|Builder $query */
        $query = Testimonial::with('student');

        $query->ofPublished();
        $query->orderBy('id', 'desc');
        return $query->get();
    }
}
