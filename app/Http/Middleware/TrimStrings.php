<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;

class TrimStrings extends Middleware
{
    /**
     * The names of the attributes that should not be trimmed.
     *
     * @var array
     */
    protected $except = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Skip trim/normalization for heavy import routes to avoid
     * large payload normalization aborts before controller logic executes.
     */
    public function handle($request, Closure $next)
    {
        if (
            $request->routeIs('pro.import.create-subproducts') ||
            $request->routeIs('pro.import.items-only')
        ) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
