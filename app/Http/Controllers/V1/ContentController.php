<?php

namespace App\Http\Controllers\V1;

use App\Models\Chapter;
use App\Models\CustomizedPackage;
use App\Models\Package;
use App\Models\PackageVideo;
use App\Models\Subject;
use App\Models\SubjectPackage;
use App\Models\OrderItem;
use App\Models\UserFreemium;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $package = Package::withTrashed()->where('id', request()->input('package'))->first();

        $orderItem = null;

        $freemiumItem = request()->input('freemium_package');

        if ($package) {
            if(!$freemiumItem){
                $orderItem = OrderItem::where('package_id', $package->id)
                    ->where('user_id', Auth::id())
                    ->where('is_canceled', false)
                    ->whereIn('payment_status', [OrderItem::PAYMENT_STATUS_FULLY_PAID, OrderItem::PAYMENT_STATUS_PARTIALLY_PAID])
                    ->whereHas('order', function($query) {
                        $query->where('is_refunded', false);
                    })
                    ->first();

                if ($package->prebook_launch_date) {
                    $orderItem->launch_date = $package->prebook_launch_date ?? null;
                }
            } else {
                $freemiumPackage = UserFreemium::where('package_id',$package->id)
                    ->where('user_id', Auth::id())
                    ->whereHas('package', function ($query){
                        $query->where('is_freemium',1);
                    })
                    ->first();
            }

            if ($orderItem || $freemiumPackage) {
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

                $packageVideos = PackageVideo::whereIn('package_id', $packageIDs)->with(['module', 'video' => function ($query) {
                    $query->with(['professor','videoHistories' => function ($query) {
                        $query->where('user_id', Auth::id());
                        if(request()->input('package')){
                            $query->where('package_id',  request()->input('package'));
                        }
                        if( request()->input('order_item')){
                            $query->where('order_item_id',  request()->input('order_item'));
                        }
                        $query->orderBy('created_at', 'desc');
                    }]);
                }])->get();

//                info($packageVideos);

                if (request()->filled('subject')) {
                    if ($package->type == 2 || $package->type == 3) {
                        $subjects = Subject::whereIn('id', $subjectIDs)->orderByRaw(DB::raw("FIELD(id, $implodedSubjectIDs)"))->where('id', request()->input('subject'))->get();
                    } else {
                        $subjects = Subject::whereIn('id', $packageVideos->pluck('video.subject_id'))->where('id', request()->input('subject'))->get();
                    }
                } else {
                    if ($package->type == 2 || $package->type == 3) {
                        $subjects = Subject::whereIn('id', $subjectIDs)->orderByRaw(DB::raw("FIELD(id, $implodedSubjectIDs)"))->get();
                    } else {
                        $subjects = Subject::whereIn('id', $packageVideos->pluck('video.subject_id'))->get();
                    }
                }

//                info('subjects');
//                info($subjects);

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
                $response = [];
            }
        } else {
            $response = [];
        }

        return $this->jsonResponse('Contents', $response);
    }
}
