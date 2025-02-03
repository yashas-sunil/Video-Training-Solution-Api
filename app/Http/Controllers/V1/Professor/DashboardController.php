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

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return mixed
     */
    public function index()
    {
        $draftedVideosCount = Video::query()->where('professor_id', request()->input('professor_id'))
            ->where('is_published', false)
            ->count();

        $publishedVideosCount = Video::query()->where('professor_id', request()->input('professor_id'))
            ->where('is_published', true)
            ->count();

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

//        $professorPackagesCount = count($chapterPackageIDs) + count($subjectPackageIDs) + count($customizePackageIDs);

        $professorPackagesCount = Package::query()
            ->where(function ($query) use ($chapterPackageIDs, $subjectPackageIDs, $customizePackageIDs) {
                $query->whereIn('id', $chapterPackageIDs)
                    ->orWhereIn('id', $subjectPackageIDs)
                    ->orWhereIn('id', $customizePackageIDs);
            })
            ->count();

        $packagesPurchaseCount = OrderItem::query()
            ->where(function($query) use ($chapterPackageIDs, $subjectPackageIDs, $customizePackageIDs) {
                $query->whereIn('package_id', $chapterPackageIDs)
                    ->orWhereIn('package_id', $subjectPackageIDs)
                    ->orWhereIn('package_id', $customizePackageIDs);
            })->where(function($query) {
                $query->where('payment_status', OrderItem::PAYMENT_STATUS_FULLY_PAID);
            })->count();

        $revenue = ProfessorRevenue::query()
            ->where('professor_id', request()->input('professor_id'))
            ->whereBetween('invoice_date', [Carbon::today()->startOfMonth(), Carbon::today()])
            ->whereHas('payment', function($query) {
                $query->whereHas('order', function($query) {
                    $query->where('payment_status', Order::PAYMENT_STATUS_SUCCESS);
                });
            })
            ->sum('revenue_amount');

//        $revenue= ProfessorRevenue::whereMonth('invoice_date',Carbon::now()->month)
//                                    ->whereYear('invoice_date',Carbon::now()->year)
//                                    ->sum('revenue_amount');

        return $this->jsonResponse('Dashboard', [
            'drafted_videos_count' => $draftedVideosCount,
            'published_videos_count' => $publishedVideosCount,
            'professor_packages_count' => $professorPackagesCount,
            'packages_purchase_count' => $packagesPurchaseCount,
            'revenue' => $revenue
        ]);
    }
}
