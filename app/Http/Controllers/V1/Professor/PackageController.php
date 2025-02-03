<?php

namespace App\Http\Controllers\V1\Professor;

use App\Http\Controllers\V1\Controller;
use App\Models\CustomizedPackage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\PackageVideo;
use App\Models\ProfessorRevenue;
use App\Models\SubjectPackage;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return mixed
     */
    public function index(Request $request)
    {


        $chapterPackageIDs = PackageVideo::query()->whereHas('video', function ($query) {
            $query->where('professor_id', request()->input('professor_id'));
        })->pluck('package_id')->unique()->values();

        $subjectPackageIDs = SubjectPackage::query()->wherein('chapter_package_id', $chapterPackageIDs)
            ->pluck('package_id')
            ->unique()->values();

        $customizePackageIDs = CustomizedPackage::query()->whereIn('selected_package_id', $chapterPackageIDs)
            ->orWhereIn('selected_package_id', $subjectPackageIDs)
            ->pluck('package_id')
            ->unique()->values();

        $packages = Package::query()
                    ->where(function ($query) use ($chapterPackageIDs,$subjectPackageIDs,$customizePackageIDs)
                    {
                        $query->whereIn('id',$chapterPackageIDs)
                            ->orWhereIn('id',$subjectPackageIDs)
                            ->orWhereIn('id',$customizePackageIDs);
                    });

        if (request()->filled('course'))
        {
            $packages->where('course_id', request()->input('course'));
        }
        if (request()->filled('level'))
        {
            $packages->where('level_id', request()->input('level'));
        }
        if (request()->filled('subject'))
        {
            $packages->where('subject_id', request()->input('subject'));
        }
        if (request()->filled('chapter'))
        {
            $packages->where('chapter_id', request()->input('chapter'));
        }
        if (request()->filled('language'))
        {
            $packages->where('language_id', request()->input('language'));
        }

        if (request()->filled('search'))
        {
            $packages->where('name', 'like', '%' . request()->input('search') . '%');
        }


        $packages = $packages->paginate(10);


        return $this->jsonResponse('Packages',['professor_packages' =>$packages]);
    }
}
