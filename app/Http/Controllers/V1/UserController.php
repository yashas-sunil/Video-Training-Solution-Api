<?php

namespace App\Http\Controllers\V1;

use App\Models\PushNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function removeUserFromPushNotification()
    {
        $pushNotifications = PushNotification::where('user_id', Auth::id())->where('web_or_mobile_login', 'web')->get();

        foreach ($pushNotifications as $pushNotification){
            $pushNotification->delete();
        }

        return $this->jsonResponse('Deleted successfully');
    }

}
