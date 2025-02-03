<?php

namespace App\Http\Controllers\V1;

use App\Models\ReferralSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Referral;
use App\Services\ReferralService;
use Illuminate\Support\Str;

class ReferralController extends Controller
{
    /** @var $referralService */
    var $referralService;

    /**
     * ReferralController constructor.
     * @param ReferralService $service
     */
    public function __construct(ReferralService $service)
    {
        $this->referralService = $service;
    }

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
        $attributes = $request->validate([
            'name' => 'required',
            'phone' => 'required',
            'email' => 'required',
            'course_id' => 'required',
            'city' => '',
            'state' => ''
        ]);

        $attributes['user_id'] = Auth::id();
        $attributes['code'] = Str::random(16);
        $attributes['is_point_used'] = 0;

        $referral = $this->referralService->create($attributes);

        return $this->jsonResponse('Referral', $referral);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Referral  $referral
     * @return \Illuminate\Http\Response
     */
    public function show(Referral $referral)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Referral  $referral
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Referral $referral)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Referral  $referral
     * @return \Illuminate\Http\Response
     */
    public function destroy(Referral $referral)
    {
        //
    }
}
