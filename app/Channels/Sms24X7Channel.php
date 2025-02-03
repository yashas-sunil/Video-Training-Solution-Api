<?php
namespace App\Channels;

use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class Sms24X7Channel extends SmsChannel
{

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        if (! $to = $notifiable->routeNotificationFor('sms', $notification)) {
            return;
        }

        $message = $notification->toSms($notifiable);

        $params = [
            'APIKEY' => 'sib9vBzb1bb',
            'MobileNo' => '91'.$to,
            'SenderID' => 'JKShah',
            'Message' => $message,
            'ServiceName' => 'PROMOTIONAL_SPL',
        ];

        $response = Http::get("https://smsapi.24x7sms.com/api_2.0/SendSMS.aspx", $params);

        if ($response->successful()) {
            event(new NotificationSent($notifiable, $notification, self::class, $response));
        } else {
            event(new NotificationFailed($notifiable, $notification, self::class, $response));
        }
    }
}
