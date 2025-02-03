<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting;

class ReferAndEarn extends Mailable
{
    use Queueable, SerializesModels;

    var $referral;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($referral)
    {
        $this->referral = $referral;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $bcc ='';
        $bcc_ids=[];
        $bcc_setting = Setting::where('key', 'email_bcc')->first();
        $bcc = $bcc_setting->value;
        if(!empty($bcc_setting->value)){
        $bcc_ids = explode(",",$bcc);
        }

        if(count($bcc_ids) != 0){
        return $this->to($this->referral['email'])
            ->bcc($bcc_ids)
            ->subject('JKSHAH ONLINE')
            ->view('emails.refer')
            ->with('referral', $this->referral);
        }else{
            return $this->to($this->referral['email'])
            ->subject('JKSHAH ONLINE')
            ->view('emails.refer')
            ->with('referral', $this->referral);
        }
    }
}
