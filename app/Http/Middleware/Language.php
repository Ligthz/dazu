<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Redirector;
use Illuminate\Http\Request;

class Language {

    public function __construct(Redirector $redirector) {
        // $this->app = $app;
        $this->redirector = $redirector;
        // $this->request = $request;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $languages = getLanguages();
        $locale = $request->language;
        $segments = $request->segments();
        // $locale = $segments[0];

        if($request->routeIs('api.*')) {
            if(in_array($locale, $languages)) {
                \App::setLocale($request->language);
                return $next($request);
            }
            else {
                abort(404);
            }
        }

        // Make sure current locale exists.
        if($locale) {
            if(in_array($locale, $languages)) {
                if($locale == 'en') {
                    // dd(route(\Route::currentRouteName(), ''));
                    return redirect(route(\Route::currentRouteName(), ''));
                    // array_shift($segments);
                    // return $this->redirector->to(implode('/', $segments));
                }
            }
            else {
                if(array_key_exists("1", $segments)) {
                    return redirect(route(\Route::currentRouteName(), ''));
                    // array_shift($segments);
                    // return $this->redirector->to(implode('/', $segments));
                }
                else {
                    abort(404);
                }
            }

            \App::setLocale($request->language);
        }
        else {
            \App::setlocale("en");
        }

        return $next($request);
    }

}
