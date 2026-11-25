<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Mailjet\Transport\MailjetTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Mail::extend('mailjet', function (array $config = []) {
            return (new MailjetTransportFactory)->create(
                new Dsn(
                    'mailjet+api',
                    'default',
                    config('services.mailjet.key'),
                    config('services.mailjet.secret')
                )
            );
        });
    }
}