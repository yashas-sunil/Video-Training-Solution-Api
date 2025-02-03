<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting;

class OrderStatusFailed extends Mailable
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

        $bcc ='';
        $bcc_ids=[];
        $bcc_setting = Setting::where('key', 'email_bcc')->first();
        $bcc = $bcc_setting->value;
        if(!empty($bcc_setting->value)){
        $bcc_ids = explode(",",$bcc);
        }

        // if(count($bcc_ids) != 0){
        // return $this->to($this->attributes['email'])
        //      //->bcc('jeswill@datavoice.co.in')
        //     ->bcc($bcc_ids)
        //     ->subject('JKSHAH ONLINE - ORDER FAILED')
        //     ->view('emails.order.failed.mail')
        //     ->with('attributes', $this->attributes);
        // }else{
            return $this->to($this->attributes['email'])
            ->subject('JKSHAH ONLINE - ORDER FAILED')
            ->view('emails.order.failed.mail')
            ->with('attributes', $this->attributes);
        //}
    }
}
