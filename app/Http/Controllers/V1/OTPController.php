<?php

namespace App\Http\Controllers\V1;

use App\Models\Otp;
use GuzzleHttp\Client;
use App\Models\Setting;
use App\Models\EmailLog;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Mail\OtpVerificationSignUp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class OTPController extends Controller
{
    public function send(Request $request) {
        $this->validate($request, [
            'mobile' => 'required',
            'action' => 'nullable',
        ]);

        //info($request->all());
        
        $otp = null;

        if($request->filled('token')) {
            $otp = Otp::findByToken($request->token);
        }

        if(is_null($otp) || $otp->isExpired() || $otp->isVerified()) {
            $otp = Otp::create([
                'mobile' => $request->get('mobile'),
                'action' => $request->get('action') ?: Otp::ACTION_DEFAULT,
                'action_id' => $request->get('action_id')?:'0'
            ]);
        }

            $messageBody = [
                'ver' => '1.0',
                'key' => env('KARIX_APIKEY'),
                'encrpt' => '0',
                'messages' => [[
                    'dest' => [$request->get('mobile')],
                    'send' => 'JKSHAH',
                    'template_id' => env('KARIX_SMSTemplateID'),
                    'template_values' => [$otp->code],
                ]]
            ];
            $client1 = new Client(['verify' => false]);
            $client1->post('https://japi.instaalerts.zone/httpapi/JsonReceiver', ['json' => $messageBody]);

        try {
            $bcc ='';
            $bcc_ids=[];
            $bcc_setting = Setting::where('key', 'email_bcc')->first();
            $bcc = $bcc_setting->value;
            if(!empty($bcc_setting->value)){
            $bcc_ids = explode(",",$bcc);
            }
         //   info($bcc_ids);
            $attributes = [
                
                'email' => $request->email,
                'email_bcc' => $bcc_ids,
                'otp' => $otp->code,
                'name' => $request->name
            ];
            $test=  Mail::send(new OtpVerificationSignUp($attributes));
            $email_log = new EmailLog();
            $email_log->email_to = $request->email;
            $email_log->email_from = env('MAIL_FROM_ADDRESS');
            $email_log->content = "Verify OTP";
            $email_log->save();
          
          } catch (\Exception $exception) {
             info ($exception->getTraceAsString());
          }

        return $this->success('Verification code successfully send', $otp->getToken());
    }

    public function verify(Request $request) {
        $this->validate($request, [
            'token' => 'required',
            'code' => 'required|numeric'
        ]);

        Otp::verify($request->token, $request->code);
    }
}
