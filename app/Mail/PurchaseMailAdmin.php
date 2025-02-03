<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PurchaseMailAdmin extends Mailable
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

        $to = $this->attributes['admin_email'];
        $custom_to='shaifa@jkshahclasses.com';
        $bcc = $this->attributes['email_bcc'];
         if($to !='0'){
         $admin_mail = explode(",",$to);
         array_push($admin_mail,$custom_to);
         $subject = "Confirmation about  course purchase - #".$this->attributes['order_id'];
       
       for($i=0;$i<count($admin_mail);$i++){
            if(count($bcc) != 0 ){
             $this->to( $admin_mail[$i])
                    ->bcc($bcc)
                    ->subject($subject)
                    ->view('emails.purchase_success_admin')
                    ->with('attributes', $this->attributes);
            }else{
                $this->to( $admin_mail[$i])
                    ->subject($subject)
                    ->view('emails.purchase_success_admin')
                    ->with('attributes', $this->attributes);
            }
       }
       return true;
        }


    }
}
