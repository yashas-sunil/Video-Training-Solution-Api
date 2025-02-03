<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting;

class ContactUsMail extends Mailable
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
        $this->attributes['logo']=env('APP_ENV')=='production'?env('WEB_URL') . '/assets/images/logo.png':public_path('logo.png');
        $this->attributes['web'] = env('WEB_URL');
        $to=env('APP_ENV')=='production'?'helpdesk@jkshahclasses.com':'testing.team@datavoice.co.in';

        $bcc ='';
        $bcc_ids=[];
        $bcc_setting = Setting::where('key', 'email_bcc')->first();
        $bcc = $bcc_setting->value;
        if(!empty($bcc_setting->value)){
            $bcc_ids = explode(",",$bcc);
        }
        if(count($bcc_ids) != 0){
        return $this->to($to)
            ->bcc($bcc_ids)
            ->subject('JKSHAH ONLINE - MESSAGE')
            ->view('emails.contact_us')
            ->with('attributes', $this->attributes);
        }else{
            return $this->to($to)
            ->subject('JKSHAH ONLINE - MESSAGE')
            ->view('emails.contact_us')
            ->with('attributes', $this->attributes);
        }
    }
}
