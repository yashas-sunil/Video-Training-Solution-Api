<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserNotification extends Model
{
    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeOfUser(Builder $query)
    {
        return $query->where('user_id', auth('api')->id());
    }

    public function scopeOfUnread(Builder $query)
    {
        return $query->where('is_read', false);
    }

    public static function getAll($unreadOnly = false)
    {
        $query = UserNotification::query();

        $query->ofUser();
        $query->with('notification');

        if ($unreadOnly) {
            $query->ofUnread();
        }

        $query->latest()->take(5);

        return $query->get();
    }
    /*****************Added by TE******************/
    public static function getAllUnread($unreadOnly)
    {
        $query = UserNotification::query();

        $query->ofUser();
        $query->with('notification');

        if ($unreadOnly) {
            $query->ofUnread();
        }

        return $query->get();
    }
    /**************TE Ends **********************/
    public static function markAsRead()
    {
        $query = UserNotification::query();
        $query->ofUser();
        $query->ofUnread();
        $notifications = $query->get();

        foreach ($notifications as $notification) {
            $notification->is_read = true;
            $notification->save();
        }

    }
}
