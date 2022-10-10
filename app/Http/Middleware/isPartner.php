<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Auth;

use Closure;
use Illuminate\Http\Request;

class isPartner
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::user()) {
            if(Auth::user()->isPartner() != null && Auth::user()->isValidPartner() != null) {
                return $next($request);
            }
            else {
                Auth::logout();

                $request->session()->invalidate();

                $request->session()->regenerateToken();
                
                return redirect()->route('auth.loginLocale', app()->getLocale());
            }
        }

        // return redirect()->route('auth.loginLocale', app()->getLocale());
        abort(404);
    }
}
