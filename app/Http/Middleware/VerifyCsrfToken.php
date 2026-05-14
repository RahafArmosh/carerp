<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
            'plan/paytm/*',
            '/customer/paytm/*',
            'plan-pay-with-paymentwall/*',
            'invoice-pay-with-paymentwall/*',
            'iyzipay/callback/*',
            'paytab-success/*',
            '/aamarpay*',
            '/google-ads-lead',
            'api/print-jobs/*', // Print queue API endpoints (use token auth instead of CSRF)
            'api/print-jobs/pending',
            'api/print-jobs/*/complete',
            'api/print-jobs/*/fail',
    ];
    
    /**
     * Determine if the request should be excluded from CSRF verification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function inExceptArray($request)
    {
        // Check route name first (most reliable)
        $route = $request->route();
        if ($route) {
            $routeName = $route->getName();
            if ($routeName && (
                strpos($routeName, 'api.print-jobs') === 0 || 
                $routeName === 'api.print-jobs.pending' || 
                $routeName === 'api.print-jobs.complete' || 
                $routeName === 'api.print-jobs.fail'
            )) {
                return true;
            }
        }
        
        // Check path - be very permissive to catch all variations
        $path = $request->path();
        $decodedPath = $request->decodedPath();
        $uri = $request->getRequestUri();
        
        // Check if path/URI contains api/print-jobs anywhere (very permissive)
        if (strpos($path, 'api/print-jobs') !== false || 
            strpos($decodedPath, 'api/print-jobs') !== false ||
            strpos($uri, 'api/print-jobs') !== false ||
            strpos($uri, '/api/print-jobs') !== false) {
            return true;
        }
        
        // Call parent to check $except array patterns
        return parent::inExceptArray($request);
    }
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        // Check if this request should be excluded from CSRF verification
        if ($this->inExceptArray($request)) {
            return $next($request);
        }
        
        return parent::handle($request, $next);
    }
}
