<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Support\Facades\Log;

class VerifyCsrfToken extends Middleware
{
    public function handle($request, Closure $next)
    {
        $sessionToken = $request->session()->token();
        $receivedToken = $request->header('X-XSRF-TOKEN');

        // Log the session and received CSRF tokens
        Log::debug('CSRF Session Token: ' . $sessionToken);
        Log::debug('CSRF Received Token: ' . $receivedToken);

        return parent::handle($request, $next);
    }
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'register',
        'login',
    ];
}
