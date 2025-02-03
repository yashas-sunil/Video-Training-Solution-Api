<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PurchaseMail extends Mailable
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

        $bcc = $this->attributes['email_bcc_user'];
        // if(env('APP_ENV')=='production') {
        //     return $this->to($this->attributes['email'])
        //         ->subject('Congrats! Here’s the confirmation about your course purchase')
        //         ->bcc($bcc)
        //         // ->bcc(['vishal@jkshahclasses.com', 'rahuldanait@jkshahclasses.com'])
        //         // ->bcc(['vishal@jkshahclasses.com', 'rahuldanait@jkshahclasses.com', 'helpdesk@jkshahclasses.com'])
        //         ->view('emails.purchase_success_email')
        //         ->with('attributes', $this->attributes);
        // }
        // else{
            $packageIds = array();
            foreach ($this->attributes['packages'] as $value) {
                $packageIds[] = $value['id'];
            }

            if(in_array(env('TEACHER_TRAINING_COURSE'),$packageIds)){
                
                if(count($bcc) !=0){
                    return $this->to($this->attributes['email'])
                    ->bcc($bcc)
                    ->subject('Welcome to JK SHAH - Teachers Training Programme')
                    //->view('emails.purchase_success_email')
                    ->view('emails.purchase_success_teacherTrainingCourse')
                    ->with('attributes', $this->attributes);
                }else{
                    return $this->to($this->attributes['email'])
                    ->subject('Welcome to JK SHAH - Teachers Training Programme')
                    //->view('emails.purchase_success_email')
                    ->view('emails.purchase_success_teacherTrainingCourse')
                    ->with('attributes', $this->attributes);
                }
            }

            if(count($bcc) !=0){
            return $this->to($this->attributes['email'])
                ->bcc($bcc)
                ->subject('Congrats! Here’s the confirmation about your course purchase')
                //->view('emails.purchase_success_email')
                ->view('emails.new_purchase_success_mail')
                ->with('attributes', $this->attributes);
            }else{
                return $this->to($this->attributes['email'])
                ->subject('Congrats! Here’s the confirmation about your course purchase')
                //->view('emails.purchase_success_email')
                ->view('emails.new_purchase_success_mail')
                ->with('attributes', $this->attributes);
            }
      //  }


    }
}
