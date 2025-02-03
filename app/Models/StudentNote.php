<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class StudentNote extends BaseModel
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'video_id' => 'integer',
        'time' => 'integer'
    ];

    protected $appends = ['order_item_id'];

    public function getOrderItemIdAttribute()
    {
        $orderItems = OrderItem::where('package_id', $this->package_id)
            ->where('user_id', $this->user_id)
            ->get();

        if ($orderItems) {
            foreach ($orderItems as $orderItem) {
                if (!$orderItem->is_completed) {
                    return $orderItem->id;
                }
            }
        }

        return $orderItems->first()->id ?? null;
    }

    public function video() {
        return $this->belongsTo('App\Models\Video');
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeOfUser($query)
    {
        return $query->where('user_id', Auth::id());
    }

    /**
     * @param Builder $query
     * @param integer $videoID
     * @return Builder
     */
    public function scopeOfVideo($query, $videoID)
    {
        if (! $videoID) {
            return $query;
        }

        $query->where('video_id', $videoID);
    }

    public static function getAll($videoID = null, $with = [])
    {
        /** @var StudentNote | Builder $query */
        $query = StudentNote::query();

        $query->ofUser();
        $query->ofVideo($videoID);

        if (! empty($with)) {
            $query->with($with);
        }

        $query->latest();

        return $query->get();
    }
}
