<?php

namespace App\Providers;

use App\Support\Email\EmailDeliveryLogger;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
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
    }
}
