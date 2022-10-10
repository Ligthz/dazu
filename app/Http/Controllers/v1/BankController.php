<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BankController extends Controller
{

    public function index()
    {
        \App::setLocale(getCurrentLanguage($_SERVER['HTTP_REFERER']));

        try {
            $result = DB::table('quin_banks')
            ->select('bank_id', 'bank_name')
            ->orderBy('list_order')
            ->get();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response([
                "error" => [
                    "code" => "BAC001",
                    "message" => "Resources not found!"
                ]
            ], 404);
        }

        if($result) {
            return response($result, 200);
        }
        else {
            return response([
                "error" => [
                    "code" => "BAC002",
                    "message" => "Some errors occur."
                ]
            ], 401);
        }
    }
}
