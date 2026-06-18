<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstallation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sqlDumpPath = base_path('eshop_plus.sql');
        $installViewPath = resource_path('views/install.blade.php');

        // If installation files are still present → redirect to installer
        if (file_exists($sqlDumpPath) || file_exists($installViewPath)) {
            return redirect('/install')->with('error', 'Please complete the installation first.');
        }

        // Otherwise, proceed
        return $next($request);
    }
}
