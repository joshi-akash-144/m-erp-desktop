<?php

namespace App\Providers;

use Laravel\Fortify\Fortify;
use Illuminate\Support\ServiceProvider;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Prevent Fortify from registering its own login/logout/register routes
        // since this app manages those through its own auth controllers.
        // Only the 2FA management bindings (TwoFactorAuthenticationProvider, etc.)
        // from Fortify's container are used.
        Fortify::ignoreRoutes();
    }
}
