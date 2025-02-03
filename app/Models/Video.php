<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Video extends BaseModel
{
    use SoftDeletes;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'player_url',
        'demo_player_url',
        'is_purchased',
        'formatted_duration',
        'video_order'
    ];

    const UNPUBLISHED = 1;
    const PUBLISHED = 2;

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

    public function getIsPurchasedAttribute()
    {
        return $this->chapter()->whereHas('packages', function($query) {
            $query->whereHas('orderItems', function($query) {
                $query->whereHas('order', function($query) {
                    $query->where('user_id', auth('api')->id());
                });
            });
        })->exists();
    }

//    public function getPackageIdAttribute()
//    {
//        $videoPackage =  $this->packageVideos()->first();
//        return $videoPackage->package_id;
//    }
//
//    public function getOrderItemIdAttribute()
//    {
//        $orderItems = OrderItem::where('package_id', $this->package_id)
//            ->where('user_id', Auth::id())
//            ->get();
//
//        if ($orderItems) {
//            foreach ($orderItems as $orderItem) {
//                if (!$orderItem->is_completed) {
//                    return $orderItem->id;
//                }
//            }
//        }
//
//        return $orderItems->first()->id ?? null;
//    }

    public function getDemoPlayerUrlAttribute() {
        if (! $this->demo_media_id) {
            return null;
        }

        $secret = config('services.jwp.secret');
        $player = config('services.jwp.player');
        $mediaID = $this->demo_media_id;

        $path = "players/$mediaID-$player.js";
        $expires = round((time() + 3600) / 300) * 300;
        $signature = md5($path . ':' . $expires . ':' . $secret);

        $url = 'https://cdn.jwplayer.com/' . $path . '?exp=' . $expires . '&sig=' . $signature;

        return $url;
    }

    public function getFormattedDurationAttribute()
    {
        $durationInSeconds = $this->duration;
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


    public function course() {
        return $this->belongsTo('App\Models\Course');
    }

    public function level() {
        return $this->belongsTo('App\Models\Level');
    }

    public function subject() {
        return $this->belongsTo('App\Models\Subject');
    }

    public function chapter() {
        return $this->belongsTo('App\Models\Chapter');
    }

    public function chapters() {
        return $this->hasMany(Chapter::class);
    }


    public function packageVideos() {
        return $this->hasMany(PackageVideo::class);
    }

    public function getVideoOrderAttribute()
    {
        $videoOrder = PackageVideo::where('video_id', $this->id)->where('module_id', $this->module_id)->first();
        if(!$videoOrder){
           return null;
        }

        return $videoOrder->video_order;
    }

    public function professor() {
        return $this->belongsTo('App\Models\Professor');
    }

    public function studentNotes() {
        $notes = $this->hasMany('App\Models\StudentNote');

        $notes->getQuery()->where('user_id', Auth::id());

        return $notes;
    }

    public function professorNotes() {
        return $this->hasMany('App\Models\ProfessorNote');
    }

    public function questions() {
        $questions = $this->hasMany('App\Models\AskAQuestion');

        $questions->getQuery()->where('user_id', Auth::id());

        return $questions;
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }


    public function videoHistories()
    {
        return $this->hasMany(VideoHistory::class);
    }

    public function scopeOfChapter($query, $chapterId)
    {
        if ($chapterId) {
            return $query->where('chapter_id', $chapterId);
        }

        return $query;
    }

    public function scopeofProfessor($query)
    {
      return $query->where('professor_id', $query)->where('is_published',self::PUBLISHED);
    }

    public function scopeOfPublished($query)
    {
        return $query->where('publish', self::PUBLISHED);
    }

    public static function getSignedUrl($id)
    {
        $secret = config('services.jwp.secret');
        $player = config('services.jwp.player');
        $media_id = $id;

        $path = "players/$media_id-$player.js";
        $expires = round((time() + 3600) / 300) * 300;
        $signature = md5($path.':'.$expires.':'.$secret);

        $url = 'https://cdn.jwplayer.com/'.$path.'?exp='.$expires.'&sig='.$signature;

        return $url;
    }

    public static function getAll($limit=null)
    {
        $query = Video::query();
        return $query->paginate((int)$limit);

    }

    public static function formatDuration($durationInSeconds)
    {
        $h = floor($durationInSeconds / 3600);
        $resetSeconds = $durationInSeconds - $h * 3600;
        $m = floor($resetSeconds / 60);
        $resetSeconds = $resetSeconds - $m * 60;
        $s = round($resetSeconds, 3);
        $h = str_pad($h, 2, '0', STR_PAD_LEFT);
        $m = str_pad($m, 2, '0', STR_PAD_LEFT);
        $s = str_pad($s, 2, '0', STR_PAD_LEFT);

        $duration[] = $h;

        $duration[] = $m;

        $duration[] = $s;

        return implode(':', $duration);
    }


}
