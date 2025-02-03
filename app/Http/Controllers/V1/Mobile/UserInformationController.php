<?php

namespace App\Http\Controllers\V1\Mobile;

use App\Http\Controllers\V1\Controller;
use App\Models\Course;
use App\Models\LastWatchedVideo;
use App\Models\LogSession;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\User;
use App\Models\UserFreemium;
use App\Models\VideoHistory;
use App\Models\VideoHistoryLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class UserInformationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = User::find(Auth::id());

        return $this->jsonResponse('User details',  $user);
    }

    public function studentDashboard()
    {
        $totalPurchasedOrderItems = OrderItem::where('item_type', OrderItem::ITEM_TYPE_PACKAGE)
            ->where('user_id', Auth::id())
            ->where('is_canceled', false)
            ->whereIn('payment_status', [OrderItem::PAYMENT_STATUS_FULLY_PAID, OrderItem::PAYMENT_STATUS_PARTIALLY_PAID])
            ->whereHas('order', function($query) {
                $query->where('is_refunded', false);
            })
            ->get();

        $totalPurchasedOrderItemsCount = count($totalPurchasedOrderItems);

        $progressOrderItemIds = OrderItem::where('item_type', OrderItem::ITEM_TYPE_PACKAGE)
            ->where('user_id', Auth::id())
            ->where('is_canceled', false)
            ->whereIn('payment_status', [OrderItem::PAYMENT_STATUS_FULLY_PAID, OrderItem::PAYMENT_STATUS_PARTIALLY_PAID])
            ->whereHas('order', function($query) {
                $query->where('is_refunded', false);
            })
            ->whereDate('expire_at', '>=', Carbon::now())
            ->orWhere(function($query){
                return $query
                    ->where('user_id', Auth::id())
                    ->where('extended_date', '>=', Carbon::now());
            })
            ->pluck('id');

//        $progressPackageExtensionOrderItems = OrderItem::with('packageExtensions')->where('item_type', OrderItem::ITEM_TYPE_PACKAGE)
//            ->where('user_id', Auth::id())
//            ->where('is_canceled', false)
//            ->whereIn('payment_status', [OrderItem::PAYMENT_STATUS_FULLY_PAID, OrderItem::PAYMENT_STATUS_PARTIALLY_PAID])
//            ->whereHas('order', function($query) {
//                $query->where('is_refunded', false);
//            })
//            ->WhereHas('packageExtensions', function ($query){
//                $query->whereDate('extended_date','>=', Carbon::now());
//            })
//            ->get();

        $progressPackageExtensionOrderItemsIds = [];

//        foreach ($progressPackageExtensionOrderItems as $packageExtensionOrderItem){
//            array_push($progressPackageExtensionOrderItemsIds, $packageExtensionOrderItem->id);
//        }
//
//        $progressOrderItemsArray = collect($progressOrderItemIds)->merge($progressPackageExtensionOrderItemsIds);
//
//        $progressOrderItemsArray = $progressOrderItemsArray->unique();
//
//        if(count($progressOrderItemsArray) == 0) {
//            $progressOrderItemsArrayCount = count($progressOrderItemIds);
//        }
//        else{
//            $progressOrderItemsArrayCount = count($progressOrderItemsArray);
//        }

        $expiredPackageFromVideoOrderitemIds = [];
        foreach ($totalPurchasedOrderItems as $purchasedOrderItem){
            $videoHistory = VideoHistory::where('user_id', Auth::id())
                ->where('package_id', $purchasedOrderItem->package_id)
                ->where('order_item_id', $purchasedOrderItem->id)
                ->get();

            if(count($videoHistory)>0){

                $package = Package::where('id',  $purchasedOrderItem->package_id)->first();
                if($package){
                    if($videoHistory->sum('duration') >= $package->duration * $package->total_duration){
                        array_push($expiredPackageFromVideoOrderitemIds, $purchasedOrderItem->id);
                    }
                }
            }

        }

        $expiredPackageFromVideoOrderitemCount = count($expiredPackageFromVideoOrderitemIds);

        $totalCourseInProgress = count($progressOrderItemIds) - $expiredPackageFromVideoOrderitemCount;

        $totalCoureCompleted = $totalPurchasedOrderItemsCount - $totalCourseInProgress;


        $userId = Auth::id();
        $user = User::find($userId);

        $totalWatchedTime = VideoHistory::where('user_id', $userId)
            ->sum('duration');

        $lastWatchedVideo = LastWatchedVideo::where('user_id', Auth::id())->first();

        $last_session_time = null;
        $totalTimeWatched = null;
        $last_login = null;
        $time = '0 Hr 0 Mins';
        if($totalWatchedTime) {
            $totalHour =  gmdate("H", $totalWatchedTime);
            $totalMinute =  gmdate("i", $totalWatchedTime);
            $totalSeconds =  gmdate("s", $totalWatchedTime);
            if (Str::of($totalHour)->startsWith('0')){
                $totalHour = Str::substr($totalHour, 1);
            }

            if (Str::of($totalMinute)->startsWith(0)){
                $totalMinute = Str::substr($totalMinute, 1);
            }
            if (Str::of($totalSeconds)->startsWith(0)){
                $totalSeconds = Str::substr($totalSeconds, 1);
            }

            $totalWatchedTime = $totalHour.' Hr '.$totalMinute.' Mins '.$totalSeconds.' Secs';
        }
        else{
            $totalWatchedTime = '0 Hr 0 Mins';
        }
        if($lastWatchedVideo) {
            $last_session_time = $lastWatchedVideo->duration;

            $hour =  gmdate("H", $last_session_time);
            $minute =  gmdate("i", $last_session_time);
            $seconds =  gmdate("s", $last_session_time);
            if (Str::of($hour)->startsWith('0')){
                $hour = Str::substr($hour, 1);
            }

            if (Str::of($minute)->startsWith(0)){
                $minute = Str::substr($minute, 1);
            }
            if (Str::of($seconds)->startsWith(0)){
                $seconds = Str::substr($seconds, 1);
            }

            $time = $hour.' Hr '.$minute.' Mins '.$seconds.' Secs';
        }


        return $this->jsonResponse('Dashboard',
            [
                'total_purchased' => $totalPurchasedOrderItemsCount,
                'total_purchased_courses' => $totalCourseInProgress,
                'total_completed_courses' => $totalCoureCompleted,
                'last_session_time' => $time,
                'total_time_watched' => $totalWatchedTime,
                'last_login' => Carbon::parse($user->last_login)->format('d-M-Y'),
            ]);

    }

    public function updateLogSession()
    {
        $logSession = LogSession::where('user_id', Auth::id())->first();

        $lastLoginTime = Carbon::parse($logSession->last_login);
        $currentTime = Carbon::now();
        $lastSessionTime =  $lastLoginTime->diff($currentTime)->format('%H:%I:%S');

        $logSession->last_session_end_time = $lastSessionTime;
        $logSession->save();

        return $lastSessionTime;
    }

    public function myCoursePackageDetails()
    {
        $totalPurchasedOrderItems = OrderItem::where('item_type', OrderItem::ITEM_TYPE_PACKAGE)
            ->where('user_id', Auth::id())
            ->where('is_canceled', false)
            ->whereIn('payment_status', [OrderItem::PAYMENT_STATUS_FULLY_PAID, OrderItem::PAYMENT_STATUS_PARTIALLY_PAID])
            ->whereHas('order', function($query) {
                $query->where('is_refunded', false);
            })
            ->get();

        $totalPurchasedOrderItemsCount = count($totalPurchasedOrderItems);

        $progressOrderItemIds = OrderItem::where('item_type', OrderItem::ITEM_TYPE_PACKAGE)
            ->where('user_id', Auth::id())
            ->where('is_canceled', false)
            ->whereIn('payment_status', [OrderItem::PAYMENT_STATUS_FULLY_PAID, OrderItem::PAYMENT_STATUS_PARTIALLY_PAID])
            ->whereHas('order', function($query) {
                $query->where('is_refunded', false);
            })
            ->whereDate('expire_at', '>=', Carbon::now())
            ->orWhere(function($query){
                return $query
                    ->where('user_id', Auth::id())
                    ->where('extended_date', '>=', Carbon::now());
            })
            ->pluck('id');

        $expiredPackageFromVideoOrderitemIds = [];
        foreach ($totalPurchasedOrderItems as $purchasedOrderItem){
            $videoHistory = VideoHistory::where('user_id', Auth::id())
                ->where('package_id', $purchasedOrderItem->package_id)
                ->where('order_item_id', $purchasedOrderItem->id)
                ->get();

            if(count($videoHistory)>0){

                $package = Package::where('id',  $purchasedOrderItem->package_id)->first();
                if($package){
                    if($videoHistory->sum('duration') >= $package->duration * $package->total_duration){
                        array_push($expiredPackageFromVideoOrderitemIds, $purchasedOrderItem->id);
                    }
                }
            }

        }

        $expiredPackageFromVideoOrderitemCount = count($expiredPackageFromVideoOrderitemIds);

        $totalCourseInProgress = count($progressOrderItemIds) - $expiredPackageFromVideoOrderitemCount;

        $totalCoureCompleted = $totalPurchasedOrderItemsCount - $totalCourseInProgress;


        $userFreemiumItems = UserFreemium::query()->with('package')
            ->where('user_id', Auth::id())
            ->whereHas('package', function ($query){
                $query->where('is_freemium',1);
            })
            ->pluck('id');

        $totalUserFreemium = count($userFreemiumItems);
        
        return $this->jsonResponse('Dashboard',
            [
                'total_purchased' => $totalPurchasedOrderItemsCount,
                'total_completed_courses' => $totalCoureCompleted,
                'total_user_freemium' => $totalUserFreemium,
            ]);
    }

//    public function removeTokens(Request $request)
//    {
//        $user = User::find($request->user_id);
//
//        $user->tokens->each(function($token, $key) {
//            $token->delete();
//        });
//
//        $webUrl = env('WEB_URL');
//
//        $response = Http::accept('application/json')
//            ->get($webUrl.'logout');
//
//        return true;
//    }
}
