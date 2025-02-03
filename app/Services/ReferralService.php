<?php

namespace App\Services;

use App\Models\Referral;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReferAndEarn;
use App\Models\EmailLog;

class ReferralService
{
    /**
     * @param array $attributes
     * @return Referral
     */
    public function create($attributes = [])
    {
        $referral = Referral::query()->where('user_id', $attributes['user_id'])
            ->where('email', $attributes['email'])
            ->first();

        if ($referral) {
            if ($referral->point) {
                return ['exist' => true];
            }

            $referral->delete();
        }

        $referral = Referral::create($attributes);

        $attributes['user'] = Auth::user()->name;
        // $attributes['logo'] = env('WEB_URL') . '/assets/images/logo.png';
        $attributes['logo']=env('APP_ENV')=='production'?env('WEB_URL') . '/assets/images/logo.png':public_path('logo.png');
        $attributes['web'] = env('WEB_URL');
        $attributes['url'] = env('WEB_URL') . '?referral=' . $attributes['code'];

        try {
            Mail::send(new ReferAndEarn($attributes));
            $email_log = new EmailLog();
            $email_log->email_to = $attributes['email'];
            $email_log->email_from = env('MAIL_FROM_ADDRESS');
            $email_log->content = "JKSHAH ONLINE - Refer and Earn";
            $email_log->save();
        }
        catch (\Exception $exception) {
            info($exception->getMessage(), ['exception' => $exception]);
        }


        return $referral;
    }
}
