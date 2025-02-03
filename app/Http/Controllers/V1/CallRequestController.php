<?php

namespace App\Http\Controllers\V1;

use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Models\CallRequest;
use App\Services\CallRequestService;

class CallRequestController extends Controller
{
    /** @var CallRequestService */
    var $callRequestService;

    /**
     * CallRequestController constructor.
     * @param CallRequestService $service
     */
    public function __construct(CallRequestService $service)
    {
        $this->callRequestService = $service;
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
        $request->validate([
            'mobile_code' => 'required|numeric',
            'phone' => 'required|numeric'
        ]);

        if ($request->input('mobile_code') == '+91' && strlen($request->input('phone')) != 10) {
            throw ValidationException::withMessages(['phone' => 'The phone length should be 10 digits.']);
        }

        if ($request->input('mobile_code') == '+971' && strlen($request->input('phone')) != 9) {
            throw ValidationException::withMessages(['phone' => 'The phone length should be 9 digits.']);
        }

        $callRequest = new CallRequest();
        $callRequest->phone = $request->mobile_code . $request->phone;
        $callRequest->save();

        return $this->jsonResponse('Call Request created', $callRequest);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CallRequest  $callRequest
     * @return \Illuminate\Http\Response
     */
    public function show(CallRequest $callRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CallRequest  $callRequest
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CallRequest $callRequest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CallRequest  $callRequest
     * @return \Illuminate\Http\Response
     */
    public function destroy(CallRequest $callRequest)
    {
        //
    }
}
