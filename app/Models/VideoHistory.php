<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class VideoHistory extends BaseModel
{
    protected $casts = [
        'video_id' => 'integer',
        'package_id' => 'integer',
        'order_item_id' => 'integer',
        'duration' => 'integer',
        'total_duration' => 'integer',
        'position' => 'integer',
    ];

    protected $appends = [
        'remaining_duration'
    ];


    public function video()
    {
        return $this->belongsTo('App\Models\Video');
    }

    /**
     * @param Builder $query
     * @return mixed
     */
    public function scopeOfAuth($query)
    {
        return $query->where('user_id', Auth::id());
    }

    /**
     * @param Builder $query
     * @param integer $packageID
     * @return mixed
     */
    public function scopeOfPackage($query, $packageID)
    {
//        if (!$packageID) {
//            return $query;
//        }

        return $query->where('package_id', $packageID);
    }

    /**
     * @param Builder $query
     * @param integer $videoID
     * @return mixed
     */
    public function scopeOfVideo($query, $videoID)
    {
//        if (!$videoID) {
//            return $query;
//        }

        return $query->where('video_id', $videoID);
    }

    /**
     * @param Builder $query
     * @param integer $orderItemID
     * @return mixed
     */
    public function scopeOfOrderItem($query, $orderItemID)
    {
//        if (!$orderItemID) {
//            return $query;
//        }

        return $query->where('order_item_id', $orderItemID);
    }

    public static function getAll($packageID = null, $videoID = null, $orderItemID = null)
    {
        $query = self::query();

        $query->ofAuth();
        $query->ofPackage($packageID);
        $query->ofVideo($videoID);
        $query->ofOrderItem($orderItemID);

        return $query->get();
    }

    public static function isPackageExpired($packageID = null, $orderItemID = null): bool
    {
        $watchLimit = Package::find($packageID)->duration ?? null;
        $packageDuration = Package::find($packageID)->total_duration ?? 0;

        if ($watchLimit == 'unlimited' || !$packageDuration) {
            return false;
        }

        $totalDuration = floatval($watchLimit) * $packageDuration;
        $totalDurationWatched = VideoHistory::where('user_id', auth('api')->user()->id)->where('package_id', $packageID)->where('order_item_id', $orderItemID)->sum('duration');
        $remainingDuration = round($totalDuration) - round($totalDurationWatched);

        $orderItem = OrderItem::find($orderItemID) ?? null;

        $isValidityExpired = false;

        if ($orderItem) {
            if ($orderItem->expire_at <= Carbon::today()) {
                $isValidityExpired = true;
            } else {
                $isValidityExpired = false;
            }
        }

        return ($remainingDuration <= 0) || $isValidityExpired;
    }

    public function getRemainingDurationAttribute()
    {
        $packageID = $this->package_id;
        $orderItemID = $this->order_item_id;
        $watchLimit = Package::find($packageID)->duration ?? null;
        $packageDuration = Package::find($packageID)->total_duration ?? 0;

        if ($watchLimit == 'unlimited' || !$packageDuration) {
            return null;
        }

        $orderItem = OrderItem::with('packageExtensions')->find($orderItemID) ?? null;

//        info('order item');
//        info($orderItem);

        if($orderItem){
            if(count($orderItem->packageExtensions)>0){
                $extendedHours = $orderItem->packageExtensions->sum('extended_hours') ?? 0;

                $extendedHoursInSeconds =  ($extendedHours * 3600) + 0 + 0;

                $totalDuration = (floatval($watchLimit) * $packageDuration) + $extendedHoursInSeconds;

                $totalDurationWatched = VideoHistory::where('user_id', Auth::id())->where('package_id', $packageID)->where('order_item_id', $orderItemID)->sum('duration');
                $remainingDuration = round($totalDuration) - round($totalDurationWatched);

                return $remainingDuration;
            }
            else{
                $totalDuration = (floatval($watchLimit) * $packageDuration);

                $totalDurationWatched = VideoHistory::where('user_id', Auth::id())->where('package_id', $packageID)->where('order_item_id', $orderItemID)->sum('duration');
                $remainingDuration = round($totalDuration) - round($totalDurationWatched);

                return $remainingDuration;
            }

        }
        else{
            return null;
        }

    }
}
