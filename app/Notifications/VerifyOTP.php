<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Models\Otp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class VerifyOTP extends Notification
{
    use Queueable;

    /**
     * The OTP.
     *
     * @var Otp
     */
    public $otp;

    /**
     * Create a notification instance.
     *
     * @param  Otp  $otp
     */
    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return [SmsChannel::class];
    }

    /**
     * Get the text message representation of the notification
     *
     * @param  mixed      $notifiable
     *
     * @return string
     */
    // public function toSms($notifiable)
    // {
    //     return __('The OTP for your mobile number verification is :code.', ['code' => $this->otp->code]);
    // }

    public function toSms($notifiable)
    {
        return __('SignupOTP_JK');
    }

    public function toVariables($notifiable)
    {

        return "Student, The OTP for your mobile number verification is {$this->otp->code}.  Thanks J K Shah Education Private Limited.";
    }




    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(__('Verify OTP'))
            ->line(__('Use :code to verify your JKShah Online account.', ['code' => $this->otp->code]))
            ->line(Lang::get('If you did not create an account, no further action is required.'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
