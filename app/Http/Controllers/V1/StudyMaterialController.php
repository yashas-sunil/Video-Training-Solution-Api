<?php

namespace App\Http\Controllers\V1;

use App\Models\Chapter;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\PackageVideo;
use App\Models\StudyMaterial;
use App\Models\StudyMaterialV1;
use App\Models\Subject;
use Illuminate\Http\Request;
use App\Services\StudentService;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use App\Models\UserFreemium;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StudyMaterialController extends Controller
{
    /** @var Student */
    var $studentService;


    /**
     * StudentController constructor.
     * @param StudentService $service
     */
    public function __construct(StudentService $service)
    {
        $this->studentService = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $packageIDs = OrderItem::whereHas('order', function($query) {
            $query->where('user_id', Auth::id())->where('payment_status', 1)->where('is_refunded', false);
        })->pluck('package_id');

        $totalChaptersBought = PackageVideo::whereIn('package_id', $packageIDs)->with('video.chapter')->get()->pluck('video.chapter')->unique();
        $chaptersBought = array();

        foreach($totalChaptersBought as $chapter){
            $chaptersBought[] = $chapter->id;
        }
        $study_materials = StudyMaterialV1::with('course','level','subject','chapter','language','professor')
                                           ->whereIn('chapter_id',$chaptersBought)
                                           ->where('type',$request->type)
                                           ->paginate(5);

        return  $this->jsonResponse('Study Materials', $study_materials);

    }


    public function purchasedChapters() {
        $packageIDs = OrderItem::where('user_id', Auth::id())
            ->where('payment_status', OrderItem::PAYMENT_STATUS_FULLY_PAID)
            ->whereHas('order', function($query) {
                $query->where('is_refunded', false);
            })
            ->pluck('package_id')
            ->unique()
            ->values();

        $chapterIDs = StudyMaterialV1::whereHas('package_study_material', function ($query) use ($packageIDs) {
            $query->whereIn('package_id', $packageIDs);
        });

        if(request()->input('type')){
            $chapterIDs = $chapterIDs->where('type', request()->input('type'));
        }

        $chapterIDs = $chapterIDs->pluck('chapter_id')->unique()->values();

        $chapters = Chapter::whereIn('id', $chapterIDs)->get();

        return  $this->jsonResponse('Chapters Bought', $chapters);
    }

    public function getAllLanguages()
    {
        $languages = Language::orderBy('name')->cursor();

        return  $this->jsonResponse('Languages', $languages);
    }

    public function purchasedSubjects() {
        $packageIDs = OrderItem::where('user_id', Auth::id())
            ->where('payment_status', OrderItem::PAYMENT_STATUS_FULLY_PAID)
            ->whereHas('order', function($query) {
                $query->where('is_refunded', false);
            })
            ->pluck('package_id')
            ->unique()
            ->values();

        $subjectIDs = StudyMaterialV1::whereHas('package_study_material', function ($query) use ($packageIDs) {
            $query->whereIn('package_id', $packageIDs);
        });

        if(request()->input('type')){
            $subjectIDs = $subjectIDs->where('type', request()->input('type'));
        }

        $subjectIDs = $subjectIDs->pluck('subject_id')->unique()->values();

        $subjects = Subject::whereIn('id', $subjectIDs)->get();

        return  $this->jsonResponse('Subject Bought', $subjects);
    }

    public function filterStudyMaterials(Request  $request)
    {
        $study_materials = StudyMaterialV1::getAll(
                                        $request->input('type'),
                                        $request->input('subject'),
                                        $request->input('chapter'),
                                        $request->input('professor'),
                                        $request->input('language'),
                                        $request->input('search'),
                                        $request->input('page'),
                                        $request->input('limit'));

        return $this->jsonResponse('Study Materials', $study_materials);

    }

    public function dashboardStudyPlans(Request  $request)
    {
        $packageIDs = OrderItem::where('user_id', Auth::id())
            ->where('payment_status', OrderItem::PAYMENT_STATUS_FULLY_PAID)
            ->pluck('package_id')
            ->unique()
            ->values();

        $study_materials = StudyMaterialV1::where('type', $request->input('type'))
            ->whereHas('package_study_material', function($query) use ($packageIDs) {
                $query->whereIn('package_id', $packageIDs);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->jsonResponse('Study Plans', $study_materials);

    }

    public function getPackageStudyMaterials(Request $request)
    {
        $packages = OrderItem::where('user_id', Auth::id())
            ->with([
                'package.language',
                'package.packageStudyMaterials' => function ($query) use ($request) {
                    $query->with(['studyMaterial.subject',
                        'studyMaterial.course',
                        'studyMaterial.professor',
                        'studyMaterial.chapter',
                    ]);
                    $query->whereHas('studyMaterial', function ($query) use ($request){
                        $query->where('type', $request->input('type'));
                        if($request->subject){
                            $query->where('subject_id', $request->input('subject'));
                        }
                        if($request->chapter){
                            $query->where('chapter_id', $request->input('chapter'));
                        }
                        if($request->language){
                            $query->where('language_id', $request->input('language'));
                        }

                        if($request->professor){
                            $query->where('professor_id', $request->input('professor'));
                        }

                        if($request->course){
                            $query->where('course_id', $request->input('course'));
                        }
                    });
                },
            ])
            ->whereHas('package')
            ->where('payment_status', OrderItem::PAYMENT_STATUS_FULLY_PAID)
            ->whereHas('order', function ($query) {
                $query->where('is_refunded', false);
            })
            ->get();

        return $this->jsonResponse('Study Materials', $packages);
    }

    public function getTestPapersOfOrderItems(Request $request)
    {
        $packages = OrderItem::where('user_id', Auth::id())
            ->where('id', $request->order_item_id)
            ->with([
                'package.packageStudyMaterials' => function ($query) use ($request) {
                    $query->with('studyMaterial')
                          ->whereHas('studyMaterial', function ($query) use ($request){
                                $query->where('type', $request->input('type'));
                          });
                    },
            ])
            ->first();
    
        return $this->jsonResponse('Order Item Test Papers', $packages);
    }

    public function getTestPapersOfUserFreemium(Request $request)
    {
        $packages = UserFreemium::where('user_id', Auth::id())
            ->where('id', $request->freemium_id)
            ->with([
                'package.packageStudyMaterials' => function ($query) use ($request) {
                    $query->with('studyMaterial')
                          ->whereHas('studyMaterial', function ($query) use ($request){
                                $query->where('type', $request->input('type'));
                          });
                    },
                'package' => function ($query) use ($request) {
                    $query->where('is_freemium',1);
                },
            ])
            ->first();

        return $this->jsonResponse('User Freemium Test Papers', $packages);
    }

    /***Get purchased study material */
    public function getPurchasedSm(){
        $studym=OrderItem::where('user_id', auth('api')->id())->where('item_type',2)->where('payment_status',2)->whereDate('expire_at', '>=', Carbon::now())->pluck('package_id');
        return $this->jsonResponse('ValidStudyM', $studym);
    }
}
