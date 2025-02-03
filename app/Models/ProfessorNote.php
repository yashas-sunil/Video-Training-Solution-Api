<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfessorNote extends BaseModel
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $appends = [
        'formatted_duration',
    ];

    public function video() {
        return $this->belongsTo('App\Models\Video');
    }

    public function scopeOfVideo($query, $videoID)
    {
        if (! $videoID) {
            return $query;
        }

        $query->where('video_id', $videoID);
    }

    public function getFormattedDurationAttribute()
    {
        $durationInSeconds = $this->time;
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

    public static function getAll($videoID = null, $packagevideoIds, $with = [])
    {
        /** @var ProfessorNote | Builder $query */
        $query = ProfessorNote::query()->whereIn('video_id', $packagevideoIds);

        $query->ofVideo($videoID);

        if (! empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }
}
