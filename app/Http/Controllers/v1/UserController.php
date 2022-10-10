<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\QuinUser;
use App\Models\QuinRolesHistory;
use App\Http\Resources\QuinUser as QuinUserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserController extends Controller
{
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
            $user = QuinUser::with(['connectedUsers', 'connectedFile', 'connectedBank', 'connectedQuinRolesHistories.connectedQuinRoles'])->where('users_key', $key)->firstOrFail();
            return response(new QuinUserResource($user), 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response([
                "error" => [
                    "code"=>"USR001",
                    "message"=>__('errors')['USR001']
                ]
            ], 404);
        }
    }



    // public function register(Request $request){
    //     $rules = [
    //         'data.user_login' => 'required',
    //         'data.user_email' => 'required',
    //         'data.password' => 'required',
    //         'data.c_password' => 'required|same:data.password',
    //         'data.nice_name' => 'required|max:100',
    //         'data.display_name' => 'required|max:100',
    //         'data.roles' => 'required',
    //         'data.username' => 'required'
    //     ];

    //     $validator = Validator::make($request->all(), $rules);
    //     if ($validator->fails()) {
    //         //TODO Handle your error
    //         return response([
    //             "error" => [
    //                 "code"=>"USR001",
    //                 "message"=>"Unable to add, required field(s) missing!"
    //             ]
    //         ], 400);
    //     }

    //     $user = new User;
    //     $user->user_login = $request->data['user_login'];
    //     $user->user_email = $request->data['user_email'];
    //     $user->user_pass = bcrypt($request->data['password']);
    //     $user->user_nicename = $request->data['nice_name'];
    //     $user->user_url = 'http://wordpress.bigwavemall.com';
    //     $user->user_registered = Carbon::now();
    //     $user->user_activation_key = '123';
    //     $user->user_status = 0;
    //     $user->display_name = $request->data['display_name'];

    //     $quinuser = new QuinUser;
    //     $quinuser->users_key = Str::random(32);
    //     $quinuser->username =  $request->data['username'];

    //     $rolesHistory = new QuinRolesHistory;
    //     $rolesHistory->roles = $request->data['roles'];
    //     $rolesHistory->created_at = Carbon::now();

    //     try{
    //         if($user->save()){
    //             $quinuser->users_id = $user->id;
    //             $rolesHistory->users_id = $user->id;

    //             return response($user, 201);

    //             // if($quinuser->save()) {

    //             //     if ($rolesHistory->save()) {
    //             //         return response($quinuser, 201);
    //             //     }
    //             // }
    //         }
    //     }catch(\Illuminate\Database\QueryException $ex) {
    //         if($ex->getCode() === '23000') {
    //             return response([
    //                 "error" => [
    //                     "code"=>"USR002",
    //                     "message"=>"Unable to add, some errors occur!"
    //                 ]
    //             ], 400);
    //         }
    //     }
    // }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(Request $request, $key)
    {
        $rules = [
            'old_password' => 'required',
            'new_password' => 'required',
            'con_password' => 'required|same:new_password',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            //TODO Handle your error
            return response([
                "error" => [
                    "code" => "UUP001",
                    "message" => "Unable to update, required field(s) missing!"
                ]
            ], 400);
        }

        try {
            $connectedUser = QuinUser::where('users_key', $key)->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response([
                "error" => [
                    "code"=>"UUP002",
                    "message"=>"Resource not found!"
                ]
            ], 404);
        }


        $existingUser = Auth::user();

        if($existingUser == null) {
            return response([
                "error" => [
                    "code"=>"UUP002",
                    "message"=>"Resource not found!"
                ]
            ], 404);
        }

        if($existingUser->ID != $connectedUser->users_id) {
            return response([
                "error" => [
                    "code"=>"UUP002",
                    "message"=>"Resource not found!"
                ]
            ], 404);
        }

        if (!(Hash::check($request->old_password, $existingUser->user_pass))) {
            return response([
                "error" => [
                    "code"=>"UUP003",
                    "message"=>"Incorrect password!"
                ]
            ], 422);
        }

        if(strcmp($request->old_password, $request->new_password) == 0){
            return response([
                "error" => [
                    "code"=>"UUP004",
                    "message"=>"New password cannot be same as your current password!"
                ]
            ], 422);
        }
        

        $existingUser->user_pass = bcrypt($request->new_password);

        try {
            if($existingUser->save()){
                return response(
                    $connectedUser->users_key
                , 200);
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            if($ex->getCode() === '23000') {
                return response([
                    "error" => [
                        "code"=>"UUP005",
                        "message"=>"Some errors occur!"
                    ]
                ], 400);
            }
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateBankDetails(Request $request, $key)
    {
        $rules = [
            'bank_account_name' => 'required|max:100',
            'bank_name' => 'required|max:100',
            'bank_account_no' => 'required|max:50',
            'password' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            //TODO Handle your error
            return response([
                "error" => [
                    "code" => "UUB001",
                    "message" => "Unable to update, data validation failed!"
                ]
            ], 400);
        }

        try {
            $connectedUser = QuinUser::where('users_key', $key)->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response([
                "error" => [
                    "code"=>"UUB002",
                    "message"=>"Resource not found!"
                ]
            ], 404);
        }

        $existingUser = Auth::user();

        if($existingUser == null) {
            return response([
                "error" => [
                    "code"=>"USR001",
                    "message"=>"Resource not found!"
                ]
            ], 404);
        }

        if($existingUser->ID != $connectedUser->users_id) {
            return response([
                "error" => [
                    "code"=>"USR001",
                    "message"=>"Resource not found!"
                ]
            ], 404);
        }

        if (!(Hash::check($request->password, $existingUser->user_pass))) {
            return response([
                "error" => [
                    "code"=>"UUB003",
                    "message"=>"Incorrect password!"
                ]
            ], 422);
        }
        
        $connectedUser->bank_name = $request->bank_name;
        $connectedUser->bank_account_no = $request->bank_account_no;
        $connectedUser->bank_account_name = $request->bank_account_name;
        

        try {
            if($connectedUser->save()){
                return response(
                    $connectedUser->users_key
                , 200);
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            if($ex->getCode() === '23000') {
                return response([
                    "error" => [
                        "code"=>"UUB004",
                        "message"=>"Some errors occur!"
                    ]
                ], 400);
            }
        }
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateInfo(Request $request, $key)
    {
        try {
            $connectedUser = QuinUser::where('users_key', $key)->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response([
                "error" => [
                    "code"=>"UUI002",
                    "message"=>"Resource not found!"
                ]
            ], 404);
        }

        $existingUser = Auth::user();

        if($existingUser == null) {
            return response([
                "error" => [
                    "code"=>"UUI002",
                    "message"=>"Resource not found!"
                ]
            ], 404);
        }

        if($existingUser->ID != $connectedUser->users_id) {
            return response([
                "error" => [
                    "code"=>"UUI002",
                    "message"=>"Resource not found!"
                ]
            ], 404);
        }

        if (array_key_exists("address", $request->data)){
            $connectedUser->address = $request->data['address'];
        }

        if (array_key_exists("first_name", $request->data)){
            $connectedUser->fname = $request->data['first_name'];
        }

        if (array_key_exists("last_name", $request->data)){
            $connectedUser->lname = $request->data['last_name'];
        }

        if (array_key_exists("ic_passport_no", $request->data)){
            $connectedUser->ic_passport_no = $request->data['ic_passport_no'];
        }

        if (array_key_exists("birthday", $request->data)){
            $connectedUser->dob = $request->data['birthday'];
        }

        if (array_key_exists("phone", $request->data)){
            $connectedUser->contact = $request->data['phone'];
        }

        if (array_key_exists("marital_status", $request->data)){
            $connectedUser->marital_status = $request->data['marital_status'];
        }

        if (array_key_exists("race", $request->data)){
            $connectedUser->race = $request->data['race'];
        }

        if (array_key_exists("gender", $request->data)){
            $connectedUser->gender = $request->data['gender'];
        }
        
        try {
            if($connectedUser->save()){
                return response(
                    $connectedUser->users_key
                , 200);
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            if($ex->getCode() === '23000') {
                return response([
                    "error" => [
                        "code"=>"UUI003",
                        "message"=>"Some errors occur!"
                    ]
                ], 400);
            }
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateAvatar(Request $request, $key)
    {
        $rules = [
            'image_id' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            //TODO Handle your error
            return response([
                "error" => [
                    "code" => "UUA001",
                    "message" => "Unable to update, required field(s) missing!"
                ]
            ], 400);
        }

        try {
            $connectedUser = QuinUser::where('users_key', $key)->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response([
                "error" => [
                    "code"=>"UUA002",
                    "message"=>"Resource not found!"
                ]
            ], 404);
        }        

        $connectedUser->avatar = (int) $request->image_id;

        try {
            if($connectedUser->save()){
                $user = QuinUser::with(['connectedUsers', 'connectedFile', 'connectedQuinRolesHistories.connectedQuinRoles'])->where('users_key', $key)->firstOrFail();
                return response(new QuinUserResource($user), 200);
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            if($ex->getCode() === '23000') {
                return response([
                    "error" => [
                        "code"=>"UUA003",
                        "message"=>"Some errors occur!"
                    ]
                ], 400);
            }
        }
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showRank($key)
    {
        try {
            $connectedUser = User::where('referral_code', $key)->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response([
                "error" => [
                    "code"=>"URK001",
                    "message"=>"Resource not found!"
                ]
            ], 404);
        }

        try{
            $colors = DB::table('user_types')
            ->select(
                'user_types.primary_color',
                'user_types.secondary_color'
            )
            ->join('users', 'user_types.user_types_id', '=', 'users.user_type_id')
            ->where('users.referral_code', '=', $key)
            ->get();

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response([
                "error" => [
                    "code"=>"URK002",
                    "message"=>"Some errors occur!"
                ]
            ], 400);
        }

        return response([
            'primary' => $colors[0]->primary_color,
            'secondary' => $colors[0]->secondary_color
        ], 200);
    }









    

}
