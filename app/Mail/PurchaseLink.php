<?php

namespace App\Mail;

use App\Models\OrderItem;
use App\Models\Package;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting;

class PurchaseLink extends Mailable
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
        $this->attributes['image_url'] = env('ADMIN_URL'). '/storage/packages/';
        $bcc ='';
        $bcc_ids=[];
        $bcc_setting = Setting::where('key', 'email_bcc')->first();
        $bcc = $bcc_setting->value;
        if(!empty($bcc_setting->value)){
        $bcc_ids = explode(",",$bcc);
        }
        // if(count($bcc_ids) != 0){
        // return $this->to($this->attributes['email'])
        //     ->bcc($bcc_ids)
        //     ->subject('JKSHAH ONLINE - PURCHASE LINK')
        //     ->view('emails.purchase_link')
        //     ->with('referral', $this->attributes);
        // }else{
            return $this->to($this->attributes['email'])
            ->subject('JKSHAH ONLINE - PURCHASE LINK')
            ->view('emails.purchase_link')
            ->with('referral', $this->attributes);
    //    }
    }
}
