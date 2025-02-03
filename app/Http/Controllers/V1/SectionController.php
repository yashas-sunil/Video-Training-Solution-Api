<?php

namespace App\Http\Controllers\V1;

use App\Models\Package;
use App\Models\Section;
use App\Models\SectionPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\Input;

class SectionController extends Controller
{
    public function index()
    {
        $sections = Section::where('is_enabled', Section::ENABLED)->orderBy('order')->get();

        return $this->jsonResponse('Sections', $sections);
    }

    public function getSectionPackagesForHomePage(Request $request)
    {
//        $sectionPackage = Package::with('language', 'sectionPackages')
//            ->whereHas('sectionPackages', function ($query) use($request) {
//                $query->where('section_id', $request->input('section_id'));
//            })
////            ->join('section_packages', 'section_packages.package_id', '=', 'packages.id')
////            /*->whereHas('sectionPackages', function ($query) use($request) {
////                $query->where('section_id1', $request->input('section_id'));
////            })*/
////            ->where('section_packages.section_id', $request->input('section_id'))
//            ->approved()
//            ->ofNotPreBooked()
//            ->ofActive(true)
//            ->join('section_packages', 'section_packages.package_id', '=', 'packages.id')
//            ->orderBy('section_packages.order')
//            ->get();

        $sectionPackage = Package::with(['language', 'sectionPackages','orderItems' => function($query){
            $query->where('review_status', 'ACCEPTED');
        }])
            ->join('section_packages', 'packages.id', '=', 'section_packages.package_id')
            ->where('section_packages.section_id', $request->input('section_id'))
            ->select(['packages.*'])
            ->orderBy('section_packages.order')
            ->approved()
            ->ofNotPreBooked()
            ->ofActive(true)
            ->limit('16')
            ->get();

        return $this->jsonResponse('Section Packages', ['data' => $sectionPackage]);
    }
}
