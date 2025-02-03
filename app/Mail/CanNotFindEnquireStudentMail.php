<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CanNotFindEnquireStudentMail extends Mailable
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
        $this->attributes['logo'] = env('APP_ENV') == 'production' ? env('WEB_URL') . '/assets/images/logo.png' : public_path('logo.png');
        $this->attributes['web'] = env('WEB_URL');
        $to = $this->attributes['email'];

        $bcc = '';
        $bcc_ids = [];
        $bcc_setting = Setting::where('key', 'email_bcc')->first();
        $bcc = $bcc_setting->value;
        if (!empty($bcc_setting->value)) {
            $bcc_ids = explode(",", $bcc);
        }
        if (count($bcc_ids) != 0) {
            return $this->to($to)
                // ->bcc(['jeswill.sj@gmail.com', 'jeswill@datavoice.co.in'])
                ->bcc($bcc_ids)
                ->subject('JKSHAH ONLINE - ENQUIRY')
                ->view('emails.cannotfindenquire_student')
                ->with('attributes', $this->attributes);
        } else {
            return $this->to($to)
                ->subject('JKSHAH ONLINE - ENQUIRY')
                ->view('emails.cannotfindenquire_student')
                ->with('attributes', $this->attributes);
        }
    }
}
