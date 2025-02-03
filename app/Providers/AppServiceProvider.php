<?php

namespace App\Providers;

use App\Channels\SmsChannel;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\UrlGenerator;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if(env('APP_ENV')=='Production'){
            $this->app['request']->server->set('HTTPS', true);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(UrlGenerator $url)
    {
        $url->formatScheme('http');

        $this->app->bind(SmsChannel::class, function () {
            $smsDriverClass = config('constants.sms.driver_class');
            $smsDriverClass = 'App\Channels\\' . $smsDriverClass . '';

            return $this->app->make($smsDriverClass);
        });
    }
}
