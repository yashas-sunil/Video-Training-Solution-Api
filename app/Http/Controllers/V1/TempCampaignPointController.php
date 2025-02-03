<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\V1\Controller;
use App\Models\CampaignRegistration;
use App\Models\JMoney;
use App\Models\SpinWheelCampaign;
use App\Models\TempCampaignPoint;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TempCampaignPointController extends Controller
{
    public function store(Request $request)
    {

        $tempCampaignPoint = new TempCampaignPoint();

        if(auth('api')->id()){
            $exist_registration = CampaignRegistration::where('user_id',auth('api')->id())->where('campaign_id', $request->input('campaign_id'))->first();
        }else{
            $exist_registration = CampaignRegistration::where('id',$request->input('campaign_registration_id'))->where('campaign_id', $request->input('campaign_id'))->first();
        }

//info($exist_registration);


        if(!$exist_registration ){
//            info('Reg not exist');
            $campaign_Registration = new CampaignRegistration();
            $campaign_Registration->campaign_id = $request->input('campaign_id');
            $campaign_Registration->user_id = auth('api')->id();
            $campaign_Registration->country_code= auth('api')->user()->country_code;
            $campaign_Registration->phone= auth('api')->user()->phone;
            $campaign_Registration->name= auth('api')->user()->name;
            $campaign_Registration->save();

            $tempCampaignPoint->campaign_registration_id = $campaign_Registration->id;

        }else{
            $tempCampaignPoint->campaign_registration_id = $exist_registration->id;
        }

        $tempCampaignPoint->campaign_id = $request->input('campaign_id');
        $tempCampaignPoint->value = $request->input('segment_value');
        $tempCampaignPoint->value_type = $request->input('segment_value_type');
        $tempCampaignPoint->expire_at = $request->input('expire_at');
//        $tempCampaignPoint->is_reward_updated = true; // Remove this field
        $tempCampaignPoint->save();


        // Update this function
        $campaignRegistration = CampaignRegistration::query()
            ->where('user_id', auth('api')->id())
            ->where('campaign_id', $request->input('campaign_id'))
            ->first();

        $tempCampaignPointCount = TempCampaignPoint::query()
            ->where('campaign_id', $request->input('campaign_id'))
            ->where('campaign_registration_id', $campaignRegistration->id ?? $request->input('campaign_registration_id'))->count();

        return $this->jsonResponse('Temp Campaign Point', $tempCampaignPointCount);
    }

    public function getRemainingChances(Request $request)
    {
        $spinWheelCampaign = SpinWheelCampaign::query()
            ->where('id', $request->input('campaign_id'))
            ->first();

        $campaignRegistration = CampaignRegistration::query()
            ->where('user_id', auth('api')->id())
            ->where('campaign_id', $request->input('campaign_id'))
            ->first();

        $tempCampaignPointCount = TempCampaignPoint::query()
            ->where('campaign_id', $request->input('campaign_id'))
            ->where('campaign_registration_id', $campaignRegistration->id ?? $request->input('campaign_registration_id'))->count();

        $response = $spinWheelCampaign->no_of_chances - $tempCampaignPointCount;

        return $this->jsonResponse('Remaining', $response);
    }
}
