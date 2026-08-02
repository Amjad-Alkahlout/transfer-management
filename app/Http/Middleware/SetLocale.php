<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(
        Request $request,
        Closure $next,
    ): Response {

        $locale = $request->cookie(
            'locale',
            config('app.locale')
        );

        if (! in_array(
            $locale,
            config('app.available_locales')
        )) {
            $locale = config('app.fallback_locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
