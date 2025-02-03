<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifiedMail extends Mailable
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
        $this->attributes['verification_url'] = env('WEB_URL') . 'verifications/' . $this->attributes['verification_token'];
        $this->attributes['is_associate_student'] = true;

        return $this->to($this->attributes['email'])
            ->subject('VERIFIED - JKSHAH ONLINE')
            ->view('emails.welcome_mail')
            ->with('attributes', $this->attributes);
    }
}
