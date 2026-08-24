<?php

namespace App\Providers;

use App\Support\Email\EmailDeliveryLogger;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(MessageSending::class, fn (MessageSending $event): mixed => app(EmailDeliveryLogger::class)->recordSending($event));
        Event::listen(MessageSent::class, fn (MessageSent $event): mixed => app(EmailDeliveryLogger::class)->recordSent($event));

        RateLimiter::for('login', function (Request $request) {
            $username = strtolower((string) $request->input('username', ''));

            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(5)->by($username !== '' ? $username : $request->ip()),
            ];
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('lemo-ai', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();

            return $request->user()
                ? [Limit::perMinute(12)->by($key), Limit::perDay(80)->by($key)]
                : [Limit::perMinute(6)->by($key), Limit::perDay(20)->by($key)];
        });
    }
}
