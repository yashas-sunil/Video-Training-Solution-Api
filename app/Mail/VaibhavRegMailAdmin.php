<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting;

class VaibhavRegMailAdmin extends Mailable
{
    use Queueable, SerializesModels;
    var $attributes;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($attributes)
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
        // $this->attributes['logo'] = env('WEB_URL') . '/assets/images/logo.png';
        $this->attributes['logo'] =env('APP_ENV')=='production'? env('WEB_URL') . '/assets/images/logo.png':public_path('logo.png');
        $this->attributes['web'] = env('WEB_URL');

        $to = $this->attributes['admin_mail'];
        $bcc= $this->attributes['email_bcc_admin'];
        
        if($to !='0'){
        $admin_mail = explode(",",$to);
        for($i=0;$i<count($admin_mail);$i++){
        if(count($bcc) != 0){
          $this->to($admin_mail[$i])
            ->bcc($bcc)
            ->subject('JKSC Online - Thane Vaibhav Registration')
            ->view('emails.vaibhav_registration_admin')
            ->with('attributes', $this->attributes);
        }else{
            $this->to($admin_mail[$i])
            ->subject('JKSC Online - Thane Vaibhav Registration')
            ->view('emails.vaibhav_registration_admin')
            ->with('attributes', $this->attributes);
        }
        }
         return true;
        }
    }
}
