<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        
        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                // Remove potential XSS attacks
                $value = strip_tags($value, '<br><p><a><b><i><u><strong><em>');
                // Trim whitespace
                $value = trim($value);
            }
        });

        $request->merge($input);

        return $next($request);
    }
}
