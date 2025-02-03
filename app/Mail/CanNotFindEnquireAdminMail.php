<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CanNotFindEnquireAdminMail extends Mailable
{
    use Queueable, SerializesModels;
    var $attributes;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->attributes['logo'] = env('APP_ENV') == 'production' ? env('WEB_URL') . '/assets/images/logo.png' : public_path('logo.png');
        $this->attributes['web'] = env('WEB_URL');
       
        $admin_email = '';
        $admin_ids = [];
        $admin_setting = Setting::where('key', 'admin_email')->first();
        $admin_email = $admin_setting->value;
        if (!empty($admin_email)) {
            $admin_ids = explode(",", $admin_email);
        }

        $bcc = '';
        $bcc_ids = [];
        $bcc_setting = Setting::where('key', 'email_bcc')->first();
        $bcc = $bcc_setting->value;
        if (!empty($bcc_setting->value)) {
            $bcc_ids = explode(",", $bcc);
        }
         if((count($bcc_ids) != 0) && (count($admin_ids) != 0)){
            return $this->to($admin_ids)
            ->bcc($bcc_ids)
            ->subject('JKSHAH ONLINE - ENQUIRY')
            ->view('emails.cannotfindenquire_admin')
            ->with('attributes', $this->attributes);
        }

        // if((count($admin_ids) != 0)){
        //     return $this->to($admin_ids)
        //     ->subject('JKSHAH ONLINE - ENQUIRY')
        //     ->view('emails.cannotfindenquire_admin')
        //     ->with('attributes', $this->attributes);
        // }



    }
}
