<?php

namespace App\Http;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\AuthorizeToAddLockers;
use App\Http\Middleware\CheckAccess;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class Kernel extends HttpKernel
{
    protected $middleware = [
        // Other middleware...
        \Illuminate\Http\Middleware\HandleCors::class,

    ];

    protected $middlewareGroups = [
        'web' => [
            // Other middleware...
        ],

        'api' => [
            'throttle:api',
            SubstituteBindings::class,
        ],
    ];

    protected $middlewarePriority = [
        // Other middleware...
    ];
}
