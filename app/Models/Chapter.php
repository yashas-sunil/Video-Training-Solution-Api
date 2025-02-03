<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Chapter extends BaseModel
{
    use SoftDeletes;

//    protected $appends = ['is_purchased', 'total_videos', 'total_duration'];

    public function getIsPurchasedAttribute()
    {
        return $this->packages()->whereHas('orderItems', function($query) {
            $query->whereHas('order', function($query) {
                $query->where('user_id', auth('api')->id());
            });
        })->exists();
    }

    public function getTotalVideosAttribute()
    {
        return $this->videos()->count();
    }

    public function getTotalDurationAttribute()
    {
        $durationInSeconds = $this->videos()->sum('duration');
        $h = floor($durationInSeconds / 3600);
        $resetSeconds = $durationInSeconds - $h * 3600;
        $m = floor($resetSeconds / 60);
        $resetSeconds = $resetSeconds - $m * 60;
        $s = round($resetSeconds, 3);
        $h = str_pad($h, 2, '0', STR_PAD_LEFT);
        $m = str_pad($m, 2, '0', STR_PAD_LEFT);
        $s = str_pad($s, 2, '0', STR_PAD_LEFT);

        if ($h > 0) {
            $duration[] = $h;
        }

        $duration[] = $m;

        $duration[] = $s;

        return implode(':', $duration);
    }

    public function modules()
    {
        return $this->hasMany(Module::class);
    }

    public function videos() {
        return $this->hasMany(Video::class);
    }

    public function study_materials()
    {
        return $this->hasMany(StudyMaterial::class);
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function subject()
    {
        $this->belongsTo(Subject::class);
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

    /**
     * @param Builder $query
     * @param integer $subjectID
     * @return Builder
     */
    public function scopeOfSubject($query, $subjectID)
    {
        if (! $subjectID) {
            return $query;
        }

        return $query->where('subject_id', $subjectID);
    }

    /**
     * @param Builder $query
     * @param boolean $isPurchased
     * @return Builder
     */
    public function scopeOfPurchased($query, $isPurchased)
    {
        if ($isPurchased == null) {
            return $query;
        }

        if ($isPurchased == true) {
            return $query->whereHas('packages', function($query) {
                $query->whereHas('orderItems', function($query) {
                    $query->whereHas('order', function($query) {
                        $query->where('user_id', Auth::id());
                    });
                });
            });
        }

        if ($isPurchased == false) {
            return $query->whereHas('packages', function($query) {
                $query->whereHas('orderItems', function($query) {
                    $query->whereHas('order', function($query) {
                        $query->where('user_id', '!=', Auth::id());
                    });
                });
            });
        }
    }

    /**
     * @param Builder $query
     * @param integer $exclude
     * @return Builder
     */
    public function scopeOfExclude($query, $exclude)
    {
        if (empty($exclude)) {
            return $query;
        }

        $query->where('id', '!=', $exclude);
    }

//    public static function getStudyMaterials($limit=null)
//    {
//        $query = Chapter::query();
//
//        $study_materials = $query->with('study_materials')
//                                ->whereHas('study_materials')
//                                ->paginate((int)$limit);
//        return $study_materials;
//
//    }

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

    public static function getAll($courseID = null, $levelID = null, $subjectID = null, $isPurchased = null, $exclude = null, $with = null, $search = null)
    {
        /** @var Chapter | Builder $query */
        $query = Chapter::query();

        $query->ofCourse($courseID);
        $query->ofLevel($levelID);
        $query->ofSubject($subjectID);
        $query->ofPurchased($isPurchased);
        $query->where('is_enabled',true);
        $query->ofExclude($exclude);
        $query->ofSearch($search);

        if (! empty($with)) {
            if ($with == 'videos') {
                $query->with(['videos' => function($query) {
                    $query->where('is_published', true);
                }]);
            } else {
                $query->with($with);
            }
        }

        return $query->get();
    }
}
