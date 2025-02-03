<?php

namespace App\Http\Controllers\V1\Professor;

use App\Http\Controllers\V1\Controller;
use App\Models\CustomizedPackage;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\PackageVideo;
use App\Models\SubjectPackage;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return mixed
     */
    public function index()
    {
        $chapterPackageIDs = PackageVideo::query()->whereHas('video', function ($query) {
            $query->where('professor_id', request()->input('professor_id'));
        })->pluck('package_id')
            ->unique()->values();

        $subjectPackageIDs = SubjectPackage::query()->whereIn('chapter_package_id', $chapterPackageIDs)
            ->pluck('package_id')
            ->unique()->values();

        $customizedPackageIDs = CustomizedPackage::query()->whereIn('selected_package_id', $chapterPackageIDs)
            ->orWhereIn('selected_package_id', $subjectPackageIDs)
            ->pluck('package_id')
            ->unique()->values();

        $query = OrderItem::query()
            ->whereHas('order', function ($query) {
                $query->where('third_party_id', null);
            })
            ->where(function ($query) use ($chapterPackageIDs, $subjectPackageIDs, $customizedPackageIDs) {
                $query->whereIn('package_id', $chapterPackageIDs)
                    ->orWhereIn('package_id', $subjectPackageIDs)
                    ->orWhereIn('package_id', $customizedPackageIDs);
            })->where('payment_status', OrderItem::PAYMENT_STATUS_FULLY_PAID)
            ->where('item_type', OrderItem::ITEM_TYPE_PACKAGE);

        if (request()->filled('from_date') && request()->filled('to_date')) {
            $query->whereBetween('created_at', [Carbon::parse(request()->input('from_date')), Carbon::parse(request()->input('to_date'))]);
        } else {
            $query->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        }

        $orderItems = $query->with('package', 'user')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

        return $this->jsonResponse('Reports', $orderItems);
    }
}
