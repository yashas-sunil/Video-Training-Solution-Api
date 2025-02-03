<?php

namespace App\Http\Controllers\V1;

use App\Models\Chapter;
use App\Models\CustomizedPackage;
use App\Models\Language;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\PackageVideo;
use App\Models\Professor;
use App\Models\Subject;
use App\Models\SubjectPackage;
use App\Models\VideoHistory;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PackageType;

class PackageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $packages = Package::getAll(
            $request->input('type'),
            $request->input('course'),
            $request->input('level'),
            $request->input('subject'),
            $request->input('chapter'),
            $request->input('professor'),
            $request->input('language'),
            $request->input('search'),
            $request->input('page'),
            $request->input('limit'),
            $request->input('in_random'),
            $request->input('price'),
            $request->input('levels'),
            $request->input('languages'),
            $request->input('subjects'),
            $request->input('chapters'),
            $request->input('professors'),
            $request->input('ratings')
        );
        return $this->jsonResponse('Packages', $packages);
    }

    public function getAllPackagesForHomePage(Request $request)
    {
        $packages = Package::with(['language','orderItems' => function($query){
            $query->where('review_status', 'ACCEPTED');
        }])
            ->where('level_id', $request->input('level'))
            ->approved()
            ->ofNotPreBooked()
            ->ofActive(true)
            ->orderByDesc('price')
            ->paginate(4);

        return $this->jsonResponse('Packages', $packages);
    }

    public function getAllPackagesByLevelId(Request $request)
    {
        $packages = Package::with('language')
            ->where('level_id', $request->input('level'))
            ->approved()
            ->ofNotPreBooked()
            ->ofActive(true)
            ->orderByDesc('price')
            ->get();

        return $this->jsonResponse('Packages', $packages);
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
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $package = Package::query()
            ->with(['packageStudyMaterials','orderItems' => function($query){
                $query->where('review_status', 'ACCEPTED');
            },'orderItems.user.student', 'course', 'level','video','packagetype'])
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->first();

//        return $package;

        if (! $package) {
            abort(404);
        }


        if ($package->is_approved == 0 && !request()->input('include_inactive')) {
            abort(404);
        }

        if ($package->expire_at && $package->expire_at <= Carbon::today() && !request()->input('include_inactive')) {
            abort(404);
        }

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

        $packageVideos = PackageVideo::whereIn('package_id', $packageIDs)->with('module', 'video')->get();

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

                $chapter->media_id = $packageVideos->where('video.chapter_id', $chapter->id)->where('video.has_demo', true)->first()
                    ? $packageVideos->where('video.chapter_id', $chapter->id)->where('video.has_demo', true)->pluck('video.media_id')->first()
                    : null;

                $chapter->first_video_id = $packageVideos->where('video.chapter_id', $chapter->id)->pluck('video.id')->first() ?? null;

                foreach ($chapter->modules as $module) {
                    $module->videos = $packageVideos->where('module_id', $module->id)->pluck('video')->unique('id')->values();
                }
            }
        }

        $package->subjects = $subjects;

        $isPurchased = OrderItem::where('package_id', $package->id)->whereHas('order', function($query) {
            $query->where('user_id', auth('api')->id())->where('payment_status', 1);
        })->exists();

        $package->is_purchased = $isPurchased;

         $language = Language::where('id',$package->language_id)->first();
         $package->language = $language;

        return $this->jsonResponse('Package', $package);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Package  $package
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Package $package)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Package  $package
     * @return \Illuminate\Http\Response
     */
    public function destroy(Package $package)
    {
        //
    }

    public function getPackageDetails($id)
    {
        $package = Package::query()
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->with('course', 'level', 'language')
            ->first();

        return $this->jsonResponse('Package', $package);
    }

    public function getPurchasedPackages(Request $request)
    {

            $orderItems = OrderItem::query()->with('package.videos')
                ->where('item_type', OrderItem::ITEM_TYPE_PACKAGE)
                ->where('user_id', Auth::id())
                ->where('is_canceled', false)
                ->whereIn('payment_status', [OrderItem::PAYMENT_STATUS_FULLY_PAID, OrderItem::PAYMENT_STATUS_PARTIALLY_PAID])
                ->whereHas('order', function($query) {
                    $query->where('is_refunded', false);
                })
                ->whereHas('package', function ($query) use ($request){
                    if ($request->filter) {
                        $query->where('name', 'like', '%' . $request->filter . '%')
                        ->orWhereHas('course', function ($query) use ($request) {
                            $query->where('name', 'like', '%' . $request->filter . '%');
                        });
                    }

                    if ($request->package_id) {
                        $query->where('id', $request->package_id);
                    }

                    if ($request->subject) {
                        $query->where('subject_id', $request->subject);
                    }
                    if ($request->recent_view) {
                        $query->whereHas('videoHistories', function ($query) use ($request){
                            if ($request->recent_view == 1) {
                                $query->latest();
                            }
                            if ($request->recent_view == 2) {
                                $query->whereDate('created_at', '>', Carbon::now()->subWeek());
                            }
                            if ($request->recent_view == 3) {
                                $query->whereDate('created_at', '>', Carbon::now()->subMonth());
                            }
                        });
                    }
                    if ($request->professor) {
                        $query->whereHas('videos', function ($query) use ($request){
                            $query->where('professor_id', $request->professor);
                        });
                    }
                })
                ->limit($request->limit)
                ->get();
        $response = $orderItems;
        return $this->jsonResponse('Purchased Packages', $response);
    }

    public function getDashboardPurchasedPackage(Request $request)
    {

        $orderItems = OrderItem::query()->with('package')
            ->where('item_type', OrderItem::ITEM_TYPE_PACKAGE)
            ->where('user_id', Auth::id())
            ->where('is_canceled', false)
            ->whereIn('payment_status', [OrderItem::PAYMENT_STATUS_FULLY_PAID, OrderItem::PAYMENT_STATUS_PARTIALLY_PAID])
            ->whereHas('order', function($query) {
                $query->where('is_refunded', false);
            })
            ->whereHas('package', function ($query) use ($request){
                if ($request->filter) {
                    $query->where('name', 'like', '%' . $request->filter . '%');
//                        ->orWhereHas('course', function ($query) use ($request) {
//                            $query->where('name', 'like', '%' . $request->filter . '%');
//                        });
                }
                if ($request->subject) {
                    $query->where('subject_id', $request->subject);
                }
                if ($request->recent_view && $request->recent_view != 4 ) {
                    $query->whereHas('videoHistories', function ($query) use ($request){
                        if ($request->recent_view == 1) {
                            $query->latest();
                        }
                        if ($request->recent_view == 2) {

                            $query->whereDate('created_at', '>', Carbon::now()->subWeek());
                        }
                        if ($request->recent_view == 3) {
                            $query->whereDate('created_at', '>', Carbon::now()->subMonth());
                        }
                    });
                }
                if ($request->professor) {
                    $query->whereHas('videos', function ($query) use ($request){
                        $query->where('professor_id', $request->professor);
                    });
                }
            })
            ->limit($request->limit)
            ->get();
            /***************Added BY TE *************/
            foreach ($orderItems as $orderItem){
               
            $language = Language::where('id',$orderItem['package']->language_id)->first(); 
            $orderItem['package']['language']=$language;
            }
            /**********END******************************/
            $response = $orderItems;

        return $this->jsonResponse('Purchased Packages', $response);
    }

    public function getPackageSubjects($packageID)
    {
        $packageVideos = PackageVideo::where('package_id', $packageID)->with('video')->get();
        $subjects = Subject::whereIn('id', $packageVideos->pluck('video.subject_id'))->get();

        $response = $subjects;

        return $this->jsonResponse('Package Subjects', $response);
    }

    public function getTotalChapters()
    {
        $totalChapters = PackageVideo::with('video.chapter')->get()->pluck('video.chapter')->unique();
        $totalChapters = count($totalChapters) ?? 0;

        $packageIDs = OrderItem::where('user_id', Auth::id())
            ->whereIn('payment_status', [OrderItem::PAYMENT_STATUS_FULLY_PAID, OrderItem::PAYMENT_STATUS_PARTIALLY_PAID])
            ->whereHas('order', function($query) {
                $query->where('is_refunded', false);
            })
            ->pluck('package_id');

        $packages = Package::withTrashed()->whereIn('id', $packageIDs)->get();

        $packageIDs = [];

        foreach ($packages as $package) {
            if ($package->type == 2) {
                $subjectPackageIDs = SubjectPackage::where('package_id', $package->id)->get()->pluck('chapter_package_id');

                foreach ($subjectPackageIDs as $subjectPackageID) {
                    $packageIDs[] = $subjectPackageID;
                }
            } else {
                $packageIDs[] = $package->id;
            }
        }

        $totalChaptersBought = PackageVideo::whereIn('package_id', $packageIDs)->with('video.chapter')->get()->pluck('video.chapter')->unique();
        $totalChaptersBought = count($totalChaptersBought) ?? 0;

        return $this->jsonResponse('Total Chapters', ['total_chapters' => $totalChapters, 'total_chapters_bought' => $totalChaptersBought]);
    }

    public function getCompletedPackages($totalPurchasedOrderItemsCount)
    {
        $totalPurchasedOrderItems = OrderItem::with(['package' => function($query) {
            $query->withTrashed();
        },])->where('item_type', OrderItem::ITEM_TYPE_PACKAGE)
            ->where('user_id', Auth::id())
            ->where('is_canceled', false)
            ->whereIn('payment_status', [OrderItem::PAYMENT_STATUS_FULLY_PAID, OrderItem::PAYMENT_STATUS_PARTIALLY_PAID])
            ->whereHas('order', function($query) {
                $query->where('is_refunded', false);
            })
            ->get();

        $totalPurchasedOrderItemsCount = count($totalPurchasedOrderItems);

        $progressOrderItemCount = OrderItem::with(['videoHistories','package' => function($query) {
            $query->withTrashed();
        }, 'packageExtensions'])->where('item_type', OrderItem::ITEM_TYPE_PACKAGE)
            ->where('user_id', Auth::id())
            ->where('is_canceled', false)
            ->whereIn('payment_status', [OrderItem::PAYMENT_STATUS_FULLY_PAID, OrderItem::PAYMENT_STATUS_PARTIALLY_PAID])
            ->whereHas('order', function($query) {
                $query->where('is_refunded', false);
            })
            ->whereDate('expire_at', '>=', Carbon::now())
            ->orWhereHas('packageExtensions', function ($query){
                $query->whereDate('extended_date','>=', Carbon::now());
            })
            ->count();


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

        $totalCourseInProgress = $progressOrderItemCount - $expiredPackageFromVideoOrderitemCount;

        $totalCoureCompleted = $totalPurchasedOrderItemsCount - $totalCourseInProgress;

        return $this->jsonResponse('Completed courses', $totalCoureCompleted);
    }

    public function getPackageList(Request $request)
    {
        $packages = Package::getPackageList(
            $request->input('search'),
            $request->input('course'),
            $request->input('level'),
            $request->input('page'),
            $request->input('limit'),
            $request->input('in_random'),
            $request->input('price'),
            $request->input('professor'),
            $request->input('levels'),
            $request->input('languages'),
            $request->input('subjects'),
            $request->input('linked_packages_id'),
            $request->input('chapters'),
            $request->input('professors'),
            $request->input('ratings'),
            $request->input('packagetypes'),
            $request->input('offer')
        );
        return $this->jsonResponse('Packages', $packages);
    }

    /**********Added by TE *****/
    public function getValidPackages()
    {
        //info(auth('api')->id());
            $validpackages=OrderItem::where('user_id', auth('api')->id())->whereDate('expire_at', '>=', Carbon::now())->pluck('package_id');
            //info(auth('api')->id());
            return $this->jsonResponse('Packages', $validpackages);
    }

    public function getpackagetypes(Request $request){
        $types = PackageType::where('is_enabled',true)->get();
        return $this->jsonResponse('PackageTypes', $types);
    }
    /*************** */
}
