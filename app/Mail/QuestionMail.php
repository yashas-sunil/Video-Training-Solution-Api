<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuestionMail extends Mailable
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
        $to = $this->attributes['admin_mail']; 
        
        // if($to !='0'){
        //     $admin_mail = explode(",",$to);
        // }
        if(count($to) != 0){
        return $this->to($this->attributes['professor_email'])
            ->bcc($to)
            ->subject('QUESTION - JKSHAH ONLINE')
            ->view('emails.question')
            ->with('attributes', $this->attributes);
        }else{
            return $this->to($this->attributes['professor_email'])
            ->subject('QUESTION - JKSHAH ONLINE')
            ->view('emails.question')
            ->with('attributes', $this->attributes);
        }
    }
}
