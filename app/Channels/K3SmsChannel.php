<?php
namespace App\Channels;

use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class K3SmsChannel extends SmsChannel
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


     //   $templateid = $notification->toSms($notifiable);

       // $templateid = $notification->toMail($notifiable);

        $smsvariable=$notification->toVariables($notifiable);

        $params = [
            'username' => 'K3JKSHAH',
            'apikey' => '67311-C0DBD',
            // 'apirequest' => 'Text',
            'apirequest' => 'Template',
            'sender' => 'JKSHAH',
            'mobile' => $to,
            // 'TemplateID' => 1107161734728917014,
            'TemplateID' =>1107161821502842076,
            'Values' => $smsvariable,
            // 'route' => 'Transactional'
            'route' => 'ServiceImplicit'
        ];

        $response = Http::withOptions(['verify' => false])->get("https://k3digitalmedia.co.in/websms/api/http/index.php", $params);

//        info ($response);

        if ($response->successful()) {
            event(new NotificationSent($notifiable, $notification, self::class, $response));
        } else {
            event(new NotificationFailed($notifiable, $notification, self::class, $response));
        }
    }
}
