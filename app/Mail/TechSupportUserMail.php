<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TechSupportUserMail extends Mailable
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
        $cc = '';
        $admin_mail = [];
        $to = $this->attributes['user_email']; 
        $bcc = $this->attributes['email_bcc'];
        $subject = "JKSHAH ONLINE - Tech Support";
        $this->attributes['logo']=env('APP_ENV')=='production'?env('WEB_URL') . '/assets/images/logo.png':public_path('logo.png');
        $this->attributes['web'] = env('WEB_URL');
        
            if(count($bcc) != 0 ){
             $this->to($to)
                    ->bcc($bcc)
                    ->subject($subject)
                    ->view('emails.techsupport_user')
                    ->with('attributes', $this->attributes);
            }else{
                $this->to($to)
                    ->subject($subject)
                    ->view('emails.techsupport_user')
                    ->with('attributes', $this->attributes);
            }
       return true;
    }
}
