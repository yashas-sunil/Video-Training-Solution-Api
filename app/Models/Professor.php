<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Professor extends BaseModel
{
    protected $guarded = ['id'];

    use SoftDeletes;
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'image_url',
        'video_url',
        'player_url',
    ];

    public function getImageUrlAttribute() {
        if ($this->image) {
            return env('IMAGE_URL').'/professors/images/'.$this->image;
        }

        return null;
    }

    public function getImageAttribute($value) {
        if (! $value) {
            return null;
        }

        return env('IMAGE_URL').'/professors/images/'.$value;
    }

    public function getVideoUrlAttribute() {
        if ($this->video) {
         return  preg_replace(
                "/\s*[a-zA-Z\/\/:\.]*youtu(be.com\/watch\?v=|.be\/)([a-zA-Z0-9\-_]+)([a-zA-Z0-9\/\*\-\_\?\&\;\%\=\.]*)/i",
                "https://www.youtube.com/embed/$2",
                $this->video);
        }

        return null;
    }

    public function getPlayerUrlAttribute() {
        $secret = config('services.jwp.secret');
        $player = config('services.jwp.player');
        $media_id = $this->media_id;

        $path = "players/$media_id-$player.js";
        $expires = round((time() + 3600) / 300) * 300;
        $signature = md5($path.':'.$expires.':'.$secret);

        $url = 'https://cdn.jwplayer.com/'.$path.'?exp='.$expires.'&sig='.$signature;

        return $url;
    }

    /**
     * Scope a query to search courses by search text.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param $search string
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where("name", "LIKE", "%$search%");
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeOfPublished($query)
    {
        return $query->where('is_published', true);
    }

    public static function getAll($search = null, $isPublished = false)
    {
        /** @var Professor|\Illuminate\Database\Eloquent\Builder $query */
        $query = Professor::query();

        if (!empty($with)) {
            $query->with($with);
        }

        if ($isPublished) {
            $query->ofPublished();
        }

        $query->search($search);
        $query->orderBy('name');
      // $query->random();

      //  $courses = $query->inRandomOrder()->limit(14)->get();
      $courses = $query->get();

        return $courses;
    }
}
