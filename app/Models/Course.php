<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends BaseModel
{
    use SoftDeletes;
    public function levels() {
        return $this->hasMany(Level::class);
    }


    /**
     * Scope a query to search courses by search text.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param $search string
     * @return Builder
     */
    public function scopeSearch($query, $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where("name", "LIKE", "%$search%");
    }

    public static function getAll($search = null, $with = [])
    {
        /** @var Course|Builder $query */
        $query = Course::query();


        if (!empty($with)) {
            $query->with($with);
        }
        $query->where('is_enabled',true);
        $query->where('display',true);
        $query->search($search);
        $query->orderBy('order');

        $courses = $query->get();

        return $courses;
    }

    /*public static function store($attributes)
    {
        $course = Course::create($attributes);

        return $course;
    }

    public static function update($attributes)
    {
        $course = Course::create($attributes);

        return $course;
    }*/
}
