<?php

namespace App\Http\Controllers\V1\Associate;

use App\Http\Controllers\V1\Controller;
use App\Models\Order;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\Associate\ProfileService;
use Illuminate\Support\Facades\Auth;
use App\Models\Associate;

class DashboardController extends Controller
{
    /** @var ProfileService */
    var $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $id = auth('api')->id();

        $date_from=Carbon::now()->subMonth();
        $date_to=Carbon::now();

        $total_orders=Order::where('associate_id',$id)
                            ->where('payment_status',1)
                            ->whereBetween('created_at',[$date_from,$date_to])
                            ->count();
        $total_commision=Order::where('associate_id',$id)
                                ->where('payment_status',1)
                                ->whereBetween('created_at',[$date_from,$date_to])
                                ->sum('commission');
        $student = Student::where('associate_id',$id)
//                          ->whereBetween('created_at',[$date_from,$date_to])
                          ->count();

        $totalPendingCommission = Order::query()
            ->where('associate_id', auth('api')->id())
            ->where('payment_status', Order::PAYMENT_STATUS_SUCCESS)
            ->where('payment_url', '!=', null)
            ->sum('commission');

        $result=['total_orders' => $total_orders, 'total_commission' => $total_commision, 'student' => $student, 'total_pending_commission' => $totalPendingCommission];

        return $this->jsonResponse('ok',$result);
    }



}
