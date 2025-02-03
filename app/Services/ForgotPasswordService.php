<?php

namespace App\Services;

use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\ForgotPassword;
use App\Models\EmailLog;
use App\Models\Setting;
use function Composer\Autoload\includeFile;

class ForgotPasswordService
{
    public function create($attributes = [])
    {
        $emailExists = User::query()->where('email', $attributes['email'])->whereIn('role', [5,6,7])->first();

//        info('Exists'. $emailExists);

        if ($emailExists) {
//            info('Yes i entered inside the exist loop');
            PasswordReset::where('email', $attributes['email'])->delete();

            $passwordReset = new PasswordReset();
            $passwordReset->email = $attributes['email'];
            $passwordReset->token = $attributes['token'];
            $passwordReset->save();
            $bcc ='';
            $bcc_ids =[];
            $bcc_setting = Setting::where('key', 'email_bcc')->first();
            $bcc = $bcc_setting->value;
            if(!empty($bcc_setting->value)){
            $bcc_ids = explode(",",$bcc);
            }
            // $attributes['logo'] = env('WEB_URL') . 'assets/images/logo.png';
            $attributes['logo']=env('APP_ENV')=='production'?env('WEB_URL') . '/assets/images/logo.png':public_path('logo.png');
            $attributes['web'] = env('WEB_URL');
            $attributes['url'] = env('WEB_URL') . 'reset-password?token=' . $attributes['token'];
            $attributes['bcc'] = $bcc_ids;
            $attributes['to_name']=$emailExists->name;
            try {
                Mail::send(new ForgotPassword($attributes));
                $email_log = new EmailLog();
                $email_log->email_to = $attributes['email'];
                $email_log->email_from = env('MAIL_FROM_ADDRESS');
                $email_log->content = "JKSHAH ONLINE - Reset Password";
                $email_log->save();
            }
            catch (\Exception $exception) {
                info($exception->getMessage(), ['exception' => $exception]);
            }
             return ['email_exist' => true];
        }
        else{
            return ['email_exist' => false];
        }

    }
}
