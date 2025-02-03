<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\V1\Controller;
use App\Models\JMoney;
use App\Models\JMoneySetting;
use App\Models\TempCampaignPoint;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JMoneyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $response = JMoney::where('points','>',0)->ofUser()->orderby('created_at','desc')->get();

        $spinWheelRewards = TempCampaignPoint::query()
            ->where('value_type', '!=', 4)
            ->where('is_used', 0)
            ->whereDate('expire_at', '>=', Carbon::today())
            ->whereHas('campaignRegistration', function ($query) {
                $query->where('user_id', auth('api')->id());
            })->get();

        return $this->jsonResponse('J-Money', ['jMoney' => $response, 'spinWheelRewards' => $spinWheelRewards]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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

    public function getMaxJkoin(){

        $max_jkoin=JMoneySetting::first()->max_jkoin;
        return $this->jsonResponse('MaxJkoin',$max_jkoin);

    }
    public function getUsedJkoin(){
       $usedjkoin =  JMoney::where('user_id', auth('api')->id())
               ->where('is_used', JMoney::USED)
               ->where('transaction_type',2)
                ->sum('points');
               return  $this->jsonResponse('UsedJkoin', $usedjkoin);
     }
}
