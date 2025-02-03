<?php

namespace App\Http\Controllers\V1;

use App\Models\LastWatchedVideo;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\VideoHistory;
use App\Models\VideoHistoryLog;
use App\Models\UserFreemium;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $videoHistories = VideoHistory::getAll(
            request()->input('package_id'),
            request()->input('video_id'),
            request()->input('order_item_id')
        );

        return $this->jsonResponse('Video Histories', $videoHistories);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function freemium_index()
    {
        $videoHistories = VideoHistory::getAll(
            request()->input('package_id'),
            request()->input('video_id'),
            request()->input('freemium_package_id')
        );

        return $this->jsonResponse('Video Histories', $videoHistories);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $videoHistory = VideoHistory::where('video_id', $request->input('video_id'))
            ->where('user_id', Auth::id())
            ->where('package_id', $request->input('package_id'))
            ->where('order_item_id', $request->input('order_item_id'))
            ->first();

        if (!$videoHistory) {
            $videoHistory = new VideoHistory();
        }

        $videoHistory->video_id = $request->input('video_id');
        $videoHistory->user_id = Auth::id();
        $videoHistory->package_id = $request->input('package_id');
        $videoHistory->order_item_id = $request->input('order_item_id');
        $videoHistory->duration = $videoHistory->duration + $request->input('duration');
        $videoHistory->total_duration = $request->input('total_duration');
        $videoHistory->position = $request->input('position');
        $videoHistory->browser_agent = $request->input('browser_agent');
        $videoHistory->save();

        $videoHistoryLog = new VideoHistoryLog();
        $videoHistoryLog->video_id = $request->input('video_id');
        $videoHistoryLog->user_id = Auth::id();
        $videoHistoryLog->package_id = $request->input('package_id');
        $videoHistoryLog->order_item_id = $request->input('order_item_id');
        $videoHistoryLog->duration = $request->input('duration');
        $videoHistoryLog->total_duration = $request->input('total_duration');
        $videoHistoryLog->position = $request->input('position');
        $videoHistoryLog->browser_agent = $request->input('browser_agent');
        $videoHistoryLog->save();

        $lastWatchedVideo = LastWatchedVideo::where('user_id', Auth::id())->first();
        if(!$lastWatchedVideo){
            $lastWatchedVideo = new LastWatchedVideo();
        }
        $lastWatchedVideo->video_id = $request->input('video_id');
        $lastWatchedVideo->user_id = Auth::id();
        $lastWatchedVideo->package_id = $request->input('package_id');
        $lastWatchedVideo->order_item_id = $request->input('order_item_id');
        $lastWatchedVideo->duration = $videoHistoryLog->duration;
        $lastWatchedVideo->position = $request->input('position');
        $lastWatchedVideo->save();

        $totalDurationWatched = VideoHistory::where('user_id', Auth::id())
            ->where('package_id', $request->input('package_id'))
            ->where('order_item_id', $request->input('order_item_id'))
            ->sum('duration');

        $package = Package::find($request->input('package_id'));
        $packageTotalDuration = $package->total_duration * $package->duration;


        $percentage = ($totalDurationWatched * 100) / $packageTotalDuration;

        $orderItem = OrderItem::find($request->input('order_item_id'));
        $orderItem->progress_percentage = $percentage;
        $orderItem->total_watched_duration = $totalDurationWatched;
        $orderItem->save();

        return $this->jsonResponse('Video History updated', $videoHistory);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function freemium_store(Request $request)
    {
        $videoHistory = VideoHistory::where('video_id', $request->input('video_id'))
            ->where('user_id', Auth::id())
            ->where('package_id', $request->input('package_id'))
            ->where('user_freemium_id', $request->input('freemium_package_id'))
            ->first();

        if (!$videoHistory) {
            $videoHistory = new VideoHistory();
        }

        $videoHistory->video_id = $request->input('video_id');
        $videoHistory->user_id = Auth::id();
        $videoHistory->package_id = $request->input('package_id');
        $videoHistory->user_freemium_id = $request->input('freemium_package_id');
        $videoHistory->duration = $videoHistory->duration + $request->input('duration');
        $videoHistory->total_duration = $request->input('total_duration');
        $videoHistory->position = $request->input('position');
        $videoHistory->browser_agent = $request->input('browser_agent');
        $videoHistory->save();

        $totalDurationWatched = VideoHistory::where('user_id', Auth::id())
            ->where('package_id', $request->input('package_id'))
            ->where('user_freemium_id', $request->input('freemium_package_id'))
            ->sum('duration');

        $package = Package::find($request->input('package_id'));
        $packageTotalDuration = $package->total_duration * $package->duration;

        $percentage = ($totalDurationWatched * 100) / $packageTotalDuration;

        $userFreemium = UserFreemium::find($request->input('freemium_package_id'));
        $userFreemium->progress_percentage = $percentage;
        $userFreemium->total_watched_duration = $totalDurationWatched;
        $userFreemium->save();

        return $this->jsonResponse('Video History updated', $videoHistory);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function getRemainingDuration()
    {
        $packageID = request()->input('package_id');
        $orderItemID = request()->input('order_item_id');
      
        $packageDuration = Package::find($packageID)->total_duration ?? 0;

     

        $orderItem = OrderItem::with('packageExtensions')->find($orderItemID) ?? null;

        $extendedHours = $orderItem->packageExtensions->sum('extended_hours') ?? 0;

        $extendedHoursInSeconds =  ($extendedHours * 3600) + 0 + 0;  
        $watchLimit = $orderItem->package_duration ?? null; 
          if ($watchLimit == 'unlimited' || !$packageDuration) {
            return null;
        }

        $totalDuration = (floatval($watchLimit) * $packageDuration) + $extendedHoursInSeconds;

        $totalDurationWatched = VideoHistory::where('user_id', Auth::id())->where('package_id', $packageID)->where('order_item_id', $orderItemID)->sum('duration');
        $remainingDuration = round($totalDuration) - round($totalDurationWatched);

        $isValidityExpired = false;

        if(count($orderItem->packageExtensions)>0) {
            $packageExtension = $orderItem->packageExtensions->last();
            if ($packageExtension->extended_date < Carbon::today()) {
                $isValidityExpired = true;
            } else {
                $isValidityExpired = false;
            }
        }
        else{
            if ($orderItem) {
                if ($orderItem->expire_at <= Carbon::today()) {
                    $isValidityExpired = true;
                }
                else {
                    $isValidityExpired = false;
                }
            }
        }

//        info($isValidityExpired);
        $watchPercentage = ($totalDurationWatched/$totalDuration)*100;

        return $this->jsonResponse('Remaining duration', [
            'watched_percentage' => $watchPercentage,
            'total_duration' => $totalDuration,
            'remaining_duration' => $remainingDuration,
            'is_validity_expired' => $isValidityExpired]);
    }

    public function latestVideoDetails()
    {
//        $videoHistory = VideoHistory::where('user_id', Auth::id())->with('video')->latest()->first();
//        info($videoHistory);

        $userId = Auth::id();
        $videoHistory = VideoHistoryLog::with('package','video.chapter')
            ->where('user_id', $userId)
            ->latest('created_at')->first();

        return $this->jsonResponse('Latest video', $videoHistory);
    }
    public function getlastCompletedVideo(){
        $userId = request()->input('user_id');
        $packageID = request()->input('package');
        $orderItemID=request()->input('orderItemId');
        $videoHistory = VideoHistoryLog::where('package_id',$packageID)
        ->where('order_item_id', $orderItemID)
        ->where('user_id',$userId)->latest()->first();
        return $this->jsonResponse('Lastvideo', $videoHistory);
    }

    public function getFreemiumRemainingDuration()
    {
        $packageID = request()->input('package_id');
        $freemiumPackageId = request()->input('freemium_package_id');

        $freemium_days_settings = Setting::where('key', 'freemium_days_max')->first();
        $freemium_days = $freemium_days_settings->value;

        $freemium_hours_settings = Setting::where('key', 'freemium_hours_max')->first();
        $freemium_hours = $freemium_hours_settings->value;
        $freemium_hours = $freemium_hours ? ($freemium_hours * 3600) : 0;

        $packageData = Package::find($packageID);
        $packageDuration = $packageData->total_duration ?? 0;
        $freemium_percentage = $packageData->freemium_content;

        $orderItem = null;
        $extendedHours = 0;
        $extendedHoursInSeconds =  ($extendedHours * 3600) + 0 + 0;
        $watchLimit = 1;
        if ($watchLimit == 'unlimited' || !$packageDuration) {
            return null;
        }
        $totalDuration = (floatval($watchLimit) * $packageDuration) + $extendedHoursInSeconds;
        $totalDurationWatched = VideoHistory::where('user_id', Auth::id())->where('package_id', $packageID)->where('user_freemium_id', $freemiumPackageId)->sum('duration');
        $remainingDuration = round($totalDuration) - round($totalDurationWatched);
        $isValidityExpired = false;
        $watchPercentage = ($totalDurationWatched/$totalDuration)*100;

        $freemiumPackage = UserFreemium::where('id',$freemiumPackageId)
            ->where('package_id', $packageID)
            ->where('user_id', Auth::id())
            ->first();

        if($freemiumPackage->created_at && $freemium_days){
            $created_date = date('Y-m-d',strtotime($freemiumPackage->created_at));
            $expiry_date = date('Y-m-d', strtotime($created_date.' + '.$freemium_days.' days'));
            if(strtotime($expiry_date) < strtotime("now")){
                $isValidityExpired = true;
            }
        }

        if(!empty($freemium_percentage) && $watchPercentage >= $freemium_percentage){
            $isValidityExpired = true;
        }
        if( $totalDurationWatched >= $freemium_hours){
            $isValidityExpired = true;
        }
        return $this->jsonResponse('Remaining duration', [
            'watched_percentage' => $watchPercentage,
            'total_duration' => $totalDuration,
            'remaining_duration' => $remainingDuration,
            'is_validity_expired' => $isValidityExpired,
        ]);
    }
}
