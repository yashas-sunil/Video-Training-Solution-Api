<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForgotPassword extends Mailable
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
        if(count($this->attributes['bcc']) !=0){
        return $this->to($this->attributes['email'])
            ->bcc($this->attributes['bcc'])
            ->subject('JKSHAH ONLINE')
            ->view('emails.forgot')
            ->with('referral', $this->attributes);
        }else{
            return $this->to($this->attributes['email'])
            ->subject('JKSHAH ONLINE')
            ->view('emails.forgot')
            ->with('referral', $this->attributes);
        }
    }
}
