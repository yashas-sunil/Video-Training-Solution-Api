<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends BaseModel
{

    use SoftDeletes;
    public function level() {
        return $this->belongsTo(Level::class);
    }

    public function course() {
        return $this->belongsTo(Course::class);
    }

    public function chapters() {
        return $this->hasMany(Chapter::class);
    }


    public function package() {
        return $this->belongsTo(Package::class);
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    /**
     * @param Builder $query
     * @param integer $courseID
     * @return Builder
     */
    public function scopeOfCourse($query, $courseID)
    {
        if (! $courseID) {
            return $query;
        }

        return $query->where('course_id', $courseID);
    }

    /**
     * @param Builder $query
     * @param integer $levelID
     * @return Builder
     */
    public function scopeOfLevel($query, $levelID)
    {
        if (! $levelID) {
            return $query;
        }

        return $query->where('level_id', $levelID);
    }

    public static function getAll($courseID = null, $levelID = null, $limit = null, $with = null)
    {
        /** @var Subject | Builder $query */
        $query = Subject::query();

        $query->ofCourse($courseID);
        $query->ofLevel($levelID);
        $query->where('is_enabled',true);
        $query->orderBy('name');

        if ($with) {
            $query->with($with);
        }

        return $query->latest()->paginate((int)$limit);
    }
    public function package_type(){
        return $this->belongsTo(PackageType::class);
    }
}
