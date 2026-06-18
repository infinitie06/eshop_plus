<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LanguageManager
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('locale')) {
            $default_locale = 'en';
            $language = Language::where('code', $default_locale)->first();
            if (!$language) {
                $language = Language::first();
                $default_locale = $language->code ?? 'en';
            }
            session()->put('locale', $default_locale);
            session()->put('is_rtl', $language->is_rtl ?? 0);
        }

        App::setLocale(session()->get('locale'));

        return $next($request);
    }
}
