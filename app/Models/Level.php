<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Level extends BaseModel
{
    use SoftDeletes;
    public function course() {
        return $this->belongsTo(Course::class);
    }

    public function subjects() {
        return $this->hasMany(Subject::class)->orderBY('name');
    }

    /**
     * Scope a query to search courses by search text.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param $search string
     * @return Builder
     */
    public function scopeOfSearch($query, $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where("name", "LIKE", "%$search%");
    }

    /**
     * Scope a query to get levels by course id.
     *
     * @param Builder $query
     * @param $courseID integer
     * @return Builder
     */
    public function scopeOfCourse($query, $courseID)
    {
        if (! $courseID) {
            return $query;
        }

        return $query->where('course_id', $courseID);
    }

    public static function getAll($courseID = null, $search = null)
    {
        /** @var Level|Builder $query */
        $query = Level::query();
        $query->ofCourse($courseID);
        $query->where('is_enabled',true);
        $query->ofSearch($search);
        $query->orderBy('order');

        $levels = $query->get();

        return $levels;
    }
}
