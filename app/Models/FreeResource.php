<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FreeResource extends Model
{
    use SoftDeletes;

    const YOUTUBE_ID = 1;
    const YOUTUBE_ID_TEXT = "YOUTUBE";
    const IMAGE = 2;
    const IMAGE_TEXT = "IMAGE";
    const NOTES = 3;
    const NOTES_TEXT = "DOCUMENT";
    const AUDIO_FILES = 4;
    const AUDIO_FILES_TEXT = "AUDIO";
    const JW_VIDEO = 5;
    const JW_VIDEO_TEXT = "JW VIDEO";

    protected $appends = [
        'video_url',
        'file_url',
        'player_url',
        'thumbnail_file_url'
    ];

    public function getFileUrlAttribute() {

        if($this->type==2){
            return env('IMAGE_URL').'/free_resources/images/'.$this->file;
        }
        elseif($this->type==3||$this->type==4){
            return env('IMAGE_URL').'/free_resources/'.$this->file;
        }

        return null;

    }

    public function getThumbnailFileUrlAttribute()
    {
        if (! $this->thumbnail_file) {
            return null;
        }

        return  env('IMAGE_URL') . '/free_resources/thumbnails/' . $this->thumbnail_file;
    }


    public function getVideoUrlAttribute() {
        if ($this->youtube_id) {
            return 'https://www.youtube.com/watch?v='.$this->youtube_id;
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

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function scopeOfType($query, $type) {
        if($type == 'videos'){
            return $query->whereIn('type',[FreeResource::YOUTUBE_ID,FreeResource::JW_VIDEO]);
        }
        elseif($type == 'audios'){
            return $query->where('type',FreeResource::AUDIO_FILES);
        }
        elseif($type == 'images'){
            return $query->where('type',FreeResource::IMAGE);
        }
        elseif($type == 'documents'){
            return $query->where('type',FreeResource::NOTES);
        }
    }

    public function scopeOfSelectedType($query, $selected_type) {
        if($selected_type == '1'){
            return $query->whereIn('type',[FreeResource::YOUTUBE_ID,FreeResource::JW_VIDEO]);
        }
        elseif($selected_type == '2'){
            return $query->where('type',FreeResource::NOTES);
        }
        return $query;
    }

    public function scopeOfProfessor($query, $professor) {
        if($professor){
            return $query->where('professor_id',$professor);
        }
    }

    public function scopeOfProfessors($query, $professors) {
        if($professors){
            return $query->whereIn('professor_id',$professors);
        }
    }

    /**
     * @param Builder $query
     * @param $courseID
     * @return Builder
     */
    public function scopeOfCourse(Builder $query, $courseID) {
        if (! $courseID) {
            return $query;
        }

        return $query->where('course_id', $courseID);
    }

    /**
     * @param Builder $query
     * @param $levelID
     * @return Builder
     */
    public function scopeOfLevel(Builder $query, $levelID) {
        if (! $levelID) {
            return $query;
        }

        return $query->where('level_id', $levelID);
    }

    public function scopeOfLevels(Builder $query, $levels) {
        if (! $levels) {
            return $query;
        }

        return $query->whereIn('level_id', $levels);
    }

    public function scopeofSearch($query, $search)
    {
        if ($search) {
            return $query->where('title', 'LIKE', '%' . $search . '%');
        }
        return $query;
    }
    public function scopeofSort($query, $sort)
    {
        if(!$sort){
            return $query->orderBy('title', 'asc');
        }
        if ($sort == 1) {
            return $query->orderBy('title', 'asc');
        }
        else if($sort == 2){
            return $query->orderBy('title', 'desc');
        }
        else{
            return $query;
        }

    }


    public static function getAll(
        $type = null,
        $selected_type = null,
        $course = null,
        $level = null,
        $professor = null,
        $search = null,
        $page = null,
        $limit = null,
        $levels = null,
        $professors = null,
        $sort = null
    ) {
        $page = $page ?: 1;
        $limit = $limit ?: 10;

        $query = FreeResource::query();

        $query->ofType($type)
              ->ofSelectedType($selected_type)
              ->ofCourse($course)
              ->ofLevel($level)
              ->ofLevels($levels)
              ->ofProfessor($professor)
              ->ofProfessors($professors)
              ->ofSort($sort)
              ->ofSearch($search);
        return $query->paginate($limit, ['*'], 'page', $page);
    }

}
