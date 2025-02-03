<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCreated extends Notification
{
    use Queueable;

    private $user;

    /**
     * Create a new notification instance.
     *
     * @param User $user
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [SmsChannel::class];
    }

    /**
     * Get the text message representation of the notification0
     *
     * @param  mixed  $notifiable
     * @return mixed
     */
    public function toSms($notifiable)
    {
        return "Dear {$this->user->name},\nThank you for enrolling with J.K. SHAH ONLINE!\nPlease check your registered email where shortly you will receive the study material for the course(s) enrolled.\nFor any queries, please write to us at helpdesk@jkshahclasses.com";
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return mixed
     */
    public function toMail($notifiable)
    {
        //
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

    public function toVariables($notifiable)
    {
        return "Student, {$this->user->name}, Thank you for enrolling with J.K. SHAH ONLINE! Please check your registered email where shortly you will receive the study material for the course(s) enrolled. For any queries, please write to us at helpdesk@jkshahclasses.com";
        // return "Student,The OTP for your mobile number verification is {$this->otp->code}.  Thanks
        // J K Shah Education Private Limited.";
    }
}
