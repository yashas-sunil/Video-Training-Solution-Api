<?php

namespace App\Http\Controllers\V1;

use App\Models\CampaignRegistration;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;

class CampaignRegistrationController extends Controller
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required',
            'country_code' => 'nullable',
            'phone' => 'required|numeric',
            'name' => 'required'
        ]);

        $campaignRegistration = new CampaignRegistration();
        $campaignRegistration->campaign_id = $request->input('campaign_id');
        $campaignRegistration->country_code = $request->input('country_code');
        $campaignRegistration->phone = $request->input('phone');
        $campaignRegistration->name = $request->input('name');
        $campaignRegistration->save();

        return $this->jsonResponse('Campaign Registration successfully done', $campaignRegistration);
    }

    public function validatePhone()
    {
        $campaignPhoneExists = CampaignRegistration::query()->where('campaign_id', request('campaign_id'))->where('phone', request('phone'))->exists();
        $userPhoneExists = User::query()->where('role', 5)->where('phone', request('phone'))->exists();

        if ($campaignPhoneExists || $userPhoneExists) {
            return 'false';
        }

        return 'true';
    }

    public function validateOTP()
    {
        try {
            $response = Otp::verify(request()->input('otp_token'), request()->input('otp_code'), 'campaign');

            if ($response == true) {
                return response()->json(true);
            } else {
                return response()->json(false);
            }

//            info ('TRY: ' . $response);
        } catch (\Exception $exception) {
            return response()->json(false);
        }
    }
}
