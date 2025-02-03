<?php

namespace App\Http\Controllers\V1;

use App\Models\Chapter;
use App\Models\CustomizedPackage;
use App\Models\Module;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\PackageVideo;
use App\Models\Subject;
use App\Models\Migration;
use App\Models\SubjectPackage;
use App\Models\VideoHistory;
use App\Models\VideoHistoryLog;
use Illuminate\Http\Request;
use App\Models\Video;
use App\Models\UserFreemium;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VideoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Video  $video
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $video = Video::where('id', $id)->with('studentNotes', 'professorNotes', 'questions')->first();

        $package = Package::withTrashed()->where('id', request()->input('package'))->first();

        $isFreemium = request()->input('freemium_package');

        $isPurchased = false;
        $freemiumPackage = false;

        if ($package) {
            if($isFreemium){
                $freemiumPackage = UserFreemium::query()->with('package')
                    ->where('user_id', Auth::id())
                    ->whereHas('package', function ($query){
                        $query->where('is_freemium',1);
                    })
                    ->first();
            } else {
                $isPurchased = OrderItem::where('id', request()->input('order_item'))
                    ->where('package_id', $package->id)
                    ->where('user_id', Auth::id())
                    ->whereIn('payment_status', [OrderItem::PAYMENT_STATUS_FULLY_PAID])
                    ->whereHas('order', function($query) {
                        $query->where('is_refunded', false);
                    })
                    ->exists();
            }
            if ($isPurchased || $freemiumPackage) {
                $packageIDs = [];

                // [START] - OLD QUERY TO GET PACKAGE

                if ($package->type == 1) {
                    $packageIDs[] = $package->id;
                }

                if ($package->type == 2) {
                    $packageIDs = SubjectPackage::where('package_id', $package->id)->get()->pluck('chapter_package_id')->toArray();
                }

                if ($package->type == 3) {
                    $selectedPackageIDs = CustomizedPackage::where('package_id', $package->id)->get()->pluck('selected_package_id')->toArray();
                    $selectedPackageIDs = array_unique($selectedPackageIDs);
                    $implodedSelectedPackageIDs = implode(',', $selectedPackageIDs);
                    $selectedPackages = Package::whereIn('id', $selectedPackageIDs)->orderByRaw(DB::raw("FIELD(id, $implodedSelectedPackageIDs)"))->get();

                    foreach ($selectedPackages as $selectedPackage) {
                        if ($selectedPackage->type == 1) {
                            $packageIDs[] = $selectedPackage->id;
                        }

                        if ($selectedPackage->type == 2) {
                            $selectedChapterPackageIDs = SubjectPackage::where('package_id', $selectedPackage->id)->get()->pluck('chapter_package_id');

                            foreach ($selectedChapterPackageIDs as $selectedChapterPackageID) {
                                $packageIDs[] = $selectedChapterPackageID;
                            }
                        }
                    }
                }

                // [END] - OLD QUERY TO GET PACKAGE

// [START] - OLD QUERY TO GET PACKAGE
//        if ($package->type == 2) {
//            $packageIDs = SubjectPackage::where('package_id', $package->id)->get()->pluck('chapter_package_id');
//        } else {
//            $packageIDs[] = $package->id;
//        }
// [END]

                if ($package->type == 2 || $package->type == 3) {
                    $packageIDs = array_unique($packageIDs);
                    $implodedPackageIDs = implode(',', $packageIDs);
                    $chapterIDs = Package::whereIn('id', $packageIDs)->orderByRaw(DB::raw("FIELD(id, $implodedPackageIDs)"))->pluck('chapter_id')->toArray();
                    $implodedChapterIDs = implode(',', $chapterIDs);

                    $subjectIDs = Package::whereIn('id', $packageIDs)->orderByRaw(DB::raw("FIELD(id, $implodedPackageIDs)"))->pluck('subject_id')->toArray();
                    $implodedSubjectIDs = implode(',', $subjectIDs);
                }

                $packageVideos = PackageVideo::whereIn('package_id', $packageIDs)->with(['module', 'video.videoHistories' => function($query) use ($packageIDs) {
                    $query->where('user_id', Auth::id())->where('package_id', $packageIDs)->where('order_item_id', request()->input('order_item'));
                }])->get();

                $videoExist = $packageVideos->where('video_id', $video->id)->first();

                if (!$videoExist) {
                    abort(404);
                }

                if ($package->type == 2 || $package->type == 3) {
                    $subjects = Subject::whereIn('id', $subjectIDs)->orderByRaw(DB::raw("FIELD(id, $implodedSubjectIDs)"))->get();
                } else {
                    $subjects = Subject::whereIn('id', $packageVideos->pluck('video.subject_id'))->get();
                }

                foreach ($subjects as $subject) {
                    if ($package->type == 2 || $package->type == 3) {
                        $chapters = Chapter::whereIn('id', $chapterIDs)->where('subject_id', $subject->id)->orderByRaw(DB::raw("FIELD(id, $implodedChapterIDs)"))->get();
                    } else {
                        $chapters = Chapter::whereIn('id', $packageVideos->pluck('video.chapter_id'))->where('subject_id', $subject->id)->get();
                    }

                    $subject->chapters = $chapters;

                    foreach($subject->chapters as $chapter) {
                        $chapter->modules = $packageVideos->where('video.chapter_id', $chapter->id)->pluck('module')->unique('id')->values();
                        $chapter->videos_count = $packageVideos->where('video.chapter_id', $chapter->id)->count() ?? null;

                        $durationInSeconds = $packageVideos->where('video.chapter_id', $chapter->id)->sum('video.duration');
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

                        $chapter->videos_total_duration = implode(':', $duration) ?? null;

                        $duration = [];

                        foreach ($chapter->modules as $module) {
                            $module->videos = $packageVideos->where('module_id', $module->id)->pluck('video')->unique('id')->values();
                        }
                    }
                }

                $response = $subjects;
            } else {
                abort(401);
            }
        } else {
            abort(401);
        }

        $video->subjects = $response;

        $secret = 'McDMAuOcJtkr7k6U172rSnjI';
        $path = 'libraries/rVFAIHjQ.js';
        $expires = round((time() + 3600) / 300) * 300;
        $signature = md5($path . ':' . $expires . ':' . $secret);
        $libraryUrl = 'https://cdn.jwplayer.com/' . $path . '?exp=' . $expires . '&sig=' . $signature;

        return $this->jsonResponse('Video', ['video' => $video, 'library_url' => $libraryUrl, 'package' => $package]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Video  $video
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Video $video)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Video  $video
     * @return \Illuminate\Http\Response
     */
    public function destroy(Video $video)
    {
        //
    }

    public function embedVideo($id)
    {
        $video = Video::where('media_id', $id)
        ->first();

        if (strpos(strtolower($video->url), 'cloudfront') === false) {
            $response = Video::getSignedUrl($id);
        }else{
            $response['type'] = 'aws-s3';

            $is_old_video = $video->is_old_video;
            $mediaID = $video->media_id;
            $res_src = array();
            $multi_res_src = json_decode($video->multi_res_src,true);
            if(!empty($is_old_video) && empty($multi_res_src)){
                $res_src = $this->get_resolution_from_migrated_asset($video, $mediaID, true);
            } else if(!empty($multi_res_src)) {
                $res_src = $multi_res_src;
            }
            $response['res_src'] = !empty($res_src) ? json_encode($res_src) : '';
            $url = $video->url;
            $response['url'] = $url;
            $pathinfo = pathinfo($url);
            $response['pathinfo'] = $pathinfo;
            $response['extension'] = !empty($pathinfo['extension']) ? $pathinfo['extension'] : 'mp4';
            $response['videoId'] = $video->id;
            $response['videoDuration'] = $video->duration;
        }

        return $this->jsonResponse('Video', $response);
    }

    public function getChapterVideos($chapterID, $videoID)
    {
        $chapterVideos = Video::where('chapter_id', $chapterID)->where('is_published', true)->get();

        return $this->jsonResponse('Chapter Videos', $chapterVideos);
    }

    public function getPlayer($id, $s3 = null)
    {
        $video = Video::findOrFail($id);

        $mediaID = null;

        if ($video->has_demo) {
            $mediaID = $video->demo_media_id;
        }

        if ($video->is_purchased) {
            $mediaID = $video->media_id;
        }
        $res_src = array();
        if($s3){
            if (strpos(strtolower($video->url), 'cloudfront') === false) {
                $secret = 'McDMAuOcJtkr7k6U172rSnjI';
                $path = 'manifests/' . $video->media_id . '.m3u8';
                $expires = round((time() + 3600) / 300) * 300;
                $signature = md5($path . ':' . $expires . ':' . $secret);
                $url = 'https://cdn.jwplayer.com/' . $path . '?exp=' . $expires . '&sig=' . $signature;
                $type='';
            } else {
                $url = $video->url;
                $type = 's3';
                $is_old_video = $video->is_old_video;
                $mediaID = $video->media_id;
                $multi_res_src = json_decode($video->multi_res_src,true);
                if(!empty($is_old_video) && empty($multi_res_src)){
                    $res_src = $this->get_resolution_from_migrated_asset($video, $mediaID, true);
                } else if(!empty($multi_res_src)) {
                    $res_src = $multi_res_src;
                }
            }
            $response = array(
                'url'   =>  $url,
                'type'  =>  $type,
                'multi_res_src' => $res_src
            );
        }else{
            $secret = 'McDMAuOcJtkr7k6U172rSnjI';
            $path = 'manifests/' . $video->media_id . '.m3u8';
            $expires = round((time() + 3600) / 300) * 300;
            $signature = md5($path . ':' . $expires . ':' . $secret);
            $url = 'https://cdn.jwplayer.com/' . $path . '?exp=' . $expires . '&sig=' . $signature;
            
            $response = $url;
        }

        return $this->jsonResponse('Player', $response);
    }

    private function get_resolution_from_migrated_asset($video, $mediaID, $force_update = false){
        $all_assets = array();
        $migrated_asset = Migration::where('media_id', $mediaID)->first();
        if(!empty($migrated_asset)){
            $s3_video_path = $migrated_asset->s3_video_path;
            $all_video_resolutions = explode(",",$migrated_asset->all_video_resolutions);
            $highest_video_resolution = $migrated_asset->highest_video_resolution;
            if(!empty($all_video_resolutions)){
                $s3_path = pathinfo($s3_video_path);
                $s3_folder_path = $s3_path['dirname'];
                $s3_file_name = $s3_path['basename'];
                foreach($all_video_resolutions as $resolution){
                    if($resolution != $highest_video_resolution){
                        $s3_url = $s3_folder_path."/$resolution/$s3_file_name";
                    } else {
                        $s3_url = $migrated_asset->s3_video_path;
                    }
                    $temp_array = array(
                        'src' => env('AWS_CDN') . $s3_url,
                        'type' => "video/mp4",
                        'label' => $resolution,
                        'res' => $resolution
                    );
                    array_push($all_assets,$temp_array);
                }
                if($force_update){
                    $video->multi_res_src = json_encode($all_assets);
                    $video->save();
                }
            }
        }
        return $all_assets;
    }

    public function getLastWatchedVideo(Request $request)
    {
        $videoHistory = VideoHistoryLog::where('package_id', $request->package)
            ->where('order_item_id', $request->order_item)
            ->where('user_id', Auth::id())->latest()->first();
        $duration = VideoHistory::where('package_id', $request->package)
            ->where('order_item_id', $request->order_item)
            ->where('video_id', $videoHistory->video_id)->first();

        $time = gmdate('H:i:s', $duration->duration);

        return $this->jsonResponse('Last watched video', ['LastWatchedVideo' => $videoHistory, 'time' => $time]);
    }
    public function getVideoById($id){
       
        $video = Video::findOrFail($id);
        if (strpos(strtolower($video->url), 'cloudfront') === false) {

        $secret = config('services.jwp.secret');
        $player = config('services.jwp.player');
        $mediaID = $video->media_id;
        $path = "players/$mediaID-$player.js";
        $expires = round((time() + 3600) / 300) * 300;
        $signature = md5("$path:$expires:$secret");

        $url = "https://cdn.jwplayer.com/$path?exp=$expires&sig=$signature";
        $response = $url;
        }else{
            $response['type'] = 'aws-s3';

            $is_old_video = $video->is_old_video;
            $mediaID = $video->media_id;
            $res_src = array();
            $multi_res_src = json_decode($video->multi_res_src,true);
            if(!empty($is_old_video) && empty($multi_res_src)){
                $res_src = $this->get_resolution_from_migrated_asset($video, $mediaID, true);
            } else if(!empty($multi_res_src)) {
                $res_src = $multi_res_src;
            }
            $response['res_src'] = !empty($res_src) ? json_encode($res_src) : '';
            $url = $video->url;
            $response['url'] = $url;
            $pathinfo = pathinfo($url);
            $response['pathinfo'] = $pathinfo;
            $response['extension'] = !empty($pathinfo['extension']) ? $pathinfo['extension'] : 'mp4';
            $response['videoId'] = $video->id;
            $response['videoDuration'] = $video->duration;
        }
        return $this->jsonResponse('Player', $response);
    }
}
