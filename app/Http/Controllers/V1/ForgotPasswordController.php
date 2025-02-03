<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use App\Services\ForgotPasswordService;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    /** @var ForgotPasswordService */
    var $forgotPasswordService;

    /**
     * ForgotPasswordController constructor.
     * @param ForgotPasswordService $forgotPasswordService
     */
    public function __construct(ForgotPasswordService $forgotPasswordService)
    {
        $this->forgotPasswordService = $forgotPasswordService;
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
            'email' => 'required'
        ]);

        $attributes['token'] = Str::random(16);

        $forgotPassword = $this->forgotPasswordService->create($attributes);

        if($forgotPassword['email_exist'] == true){
            return $this->jsonResponse('Forgot password created', $forgotPassword);
        }
        else{
            $error = ['email' => ['Email not exist']];
            return $this->jsonResponse('Forgot password created', $forgotPassword, $error, 422);
        }

//        return $this->jsonResponse('Forgot password created', $forgotPassword);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
