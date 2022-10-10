<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\QuinUser;
use Carbon\Carbon;

class FirstBDSalesController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function index($key)
    {
        \App::setLocale(getCurrentLanguage($_SERVER['HTTP_REFERER']));

        try {
            $user = QuinUser::where('users_key', $key)->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response([
                "error" => [
                    "code" => "FBDSC001",
                    "message" => "Resources not found!"
                ]
            ], 404);
        }

        $result = $user->getFirstBDCountAndSalesDetails(formatDateTimeZone(Carbon::now(), 1));
        
        if($result) {
            return response($result, 200);
        }
        else {
            return response([
                "error" => [
                    "code" => "FBDSC002",
                    "message" => "Invalid credentials."
                ]
            ], 401);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($key)
    {
        \App::setLocale(getCurrentLanguage($_SERVER['HTTP_REFERER']));

        try {
            $user = QuinUser::where('users_key', $key)->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response([
                "error" => [
                    "code" => "FBDSC001",
                    "message" => "Resources not found!"
                ]
            ], 404);
        }

        $result = $user->getFirstBDCountAndSalesOverview(formatDateTimeZone(Carbon::now(), 1));
        
        if($result) {
            return response($result, 200);
        }
        else {
            return response([
                "error" => [
                    "code" => "FBDSC002",
                    "message" => "Invalid credentials."
                ]
            ], 401);
        }
    }
}
