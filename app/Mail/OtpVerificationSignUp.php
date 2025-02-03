<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpVerificationSignUp extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $email;
    public $otpcode;
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
        $this->attributes['otpcode'] =$this->attributes['otp'];
        $this->attributes['logo'] =env('APP_ENV')=='production'? env('WEB_URL') . '/assets/images/logo.png':public_path('logo.png');
        $this->attributes['web'] = env('WEB_URL');
        $this->attributes['image_url'] = env('ADMIN_URL'). '/storage/packages/';
        $this->attributes['purchase_url'] = env('WEB_URL') . 'cart/checkout';

        if(count($this->attributes['email_bcc']) !=0){
        return $this->to($this->attributes['email'])
            ->bcc($this->attributes['email_bcc'])
            ->subject('Verify OTP')
            ->view('emails.OtpSignUp')
            ->with('attributes', $this->attributes);
        }else{
            return $this->to($this->attributes['email'])
            ->subject('Verify OTP')
            ->view('emails.OtpSignUp')
            ->with('attributes', $this->attributes);
        }  
    }
}
