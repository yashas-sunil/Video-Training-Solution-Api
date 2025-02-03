<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting;

class PaymentLink extends Mailable
{
    use Queueable, SerializesModels;

    /** @var array $attributes */
    private $attributes;

    /**
     * Create a new message instance.
     *
     * @param array $attributes
     * @return void
     */
    public function __construct($attributes = [])
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
        $this->attributes['purchase_url'] = env('WEB_URL') . 'cart/checkout';

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
        //     ->subject('PURCHASE LINK - JKSHAH ONLINE')
        //     ->view('emails.payment-link')
        //     ->with('attributes', $this->attributes);
        // }else{
            return $this->to($this->attributes['email'])
            ->subject('PURCHASE LINK - JKSHAH ONLINE')
            ->view('emails.payment-link')
            ->with('attributes', $this->attributes);
        //}
    }
}
