<?php

namespace App\Http\Controllers\v1;

use App\Models\User;
use App\Models\QuinUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{

    public function authenticate(Request $request)
    {
        $user = Auth::user();
        if($user == null) {
            return response(
                null
            , 401);
        }

        $partner = $user->isPartner();
        $validPartner = $user->isValidPartner();

        if($partner == null || $validPartner == null) {
            Auth::logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return response(
                null
            , 401);
        }

        return response([
            $partner,
            $validPartner
        ], 200);
    }

    public function login(Request $request)
    {
        \App::setLocale(getCurrentLanguage($_SERVER['HTTP_REFERER']));
        
        $credentials = [
            'login' => ['required'],
            'password' => ['required'],
        ];

        $validator = Validator::make($request->all(), $credentials);
        if ($validator->fails()) {
            //TODO Handle your error
            return response([
                "error" => [
                    "code" => "AUT001",
                    "message"=>__('errors')['AUT001']
                ]
            ], 400);
        }

        // check login type
        $login = request()->input('login');

        $login_type = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'user_email' : 'user_login';
        
        request()->merge([$login_type => $login]);


        if (Auth::attempt($request->only($login_type, 'password'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            $partner = $user->isPartner();
            $validPartner = $user->isValidPartner();

            if($partner == null || $validPartner == null) {
                Auth::logout();

                $request->session()->invalidate();

                $request->session()->regenerateToken();

                return response([
                    "error" => [
                        "code" => "AUT002",
                        "message"=>__('errors')['AUT002']
                    ]
                ], 401);
            }

            return response([
                $partner,
                $validPartner
            ], 200);
        }

        return response([
            "error" => [
                "code" => "AUT002",
                "message"=>__('errors')['AUT002']
            ]
        ], 401);
    }

    public function logout(Request $request)
    {
        if(Auth::user()) {
            Auth::logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();
        }

        return response([
            'message' => 'Logout successful.'
        ], 200);
    }
}
