<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\V1\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $response = Payment::getAll($request->input('with'),
            $request->input('page'),
            $request->input('limit'),
            $request->input('recently_viewed'));

        return $this->jsonResponse('Payments', $response);
    }
}
