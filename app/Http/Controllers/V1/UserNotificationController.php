<?php

namespace App\Http\Controllers\V1;

use App\Models\UserNotification;

class UserNotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return mixed
     */
    public function index()
    {
        $userNotifications = UserNotification::getAll(request()->input('unread_only'));

        return $this->jsonResponse('User Notifications', $userNotifications);
    }

    public function markAsRead()
    {
        UserNotification::markAsRead();

        return $this->jsonResponse('User Notifications marked as read.');
    }
    public function getUnreadCount(){
        $userNotifications = UserNotification::getAllUnread($unreadonly=true);
        $usrunreadnotiCount=count($userNotifications);
        return $this->jsonResponse('Unread Count',$usrunreadnotiCount);
        
    }
}
