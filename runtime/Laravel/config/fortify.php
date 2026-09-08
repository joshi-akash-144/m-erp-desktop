<?php

use Laravel\Fortify\Features;

return [

    'guard' => 'web',

    'middleware' => ['web'],

    'auth_middleware' => 'auth',

    'username' => 'email',

    'email' => 'email',

    'home' => '/company-selection',

    'prefix' => '',

    'domain' => null,

    'views' => true,

    'features' => [
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => false,
        ]),
    ],

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
    ],

    'password_broker' => 'users',
];
