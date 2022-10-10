<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\Request;
use App\Models\QuinUser;
use App\Models\File;
use Illuminate\Support\Facades\Validator;

class FileController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'file' => 'required|file',
            'file_name' => 'max:100',
            'created_by' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            //TODO Handle your error

            if( strpos($validator->errors()->first('file'), 'must be a file') > 0){
                return response([
                    "error" => [
                        "code"=>"FIL001",
                        "message"=>"Unable to add, file missing!"
                    ]
                ], 400);
            }

            return response([
                "error" => [
                    "code"=>"FIL002",
                    "message"=>"Unable to add, required field(s) missing!"
                ]
            ], 400);
        }


        //set filename
        if (array_key_exists("file_name", $request->all())){
            $filename = $request->file_name;
        }else{
            $filename = $request->file->getClientOriginalName();
        }

        $mime_cat = substr($request->file->getClientMimeType(),0,strpos($request->file->getClientMimeType(),'/'));

        if($mime_cat == "image"){
            $saved_file = $request->file->store('images');
        }else if($mime_cat == "application"){
            $saved_file = $request->file->store('documents');
        }else{
            return response([
                "error" => [
                    "code"=>"FIL003",
                    "message"=>"Unable to upload, unsupport format!"
                ]
            ], 400);
        }

        try {
            $connectedUser = QuinUser::where('users_key', $request->created_by)->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response([
                "error" => [
                    "code"=>"FIL004",
                    "message"=>"Resource not found!"
                ]
            ], 404);
        }

        $file = new File;
        $file->name = $filename;
        $file->mime_type = $request->file->getClientMimeType();
        $file->path = $saved_file;
        $file->created_by = $connectedUser->users_id;        
        $file->status = 1;

        try {
            if($file->save()){
                return response([
                    "avatar" => $file->id
                ], 201);
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            if($ex->getCode() === '23000') {
                return response([
                    "error" => [
                        "code"=>"FIL005",
                        "message"=>"Unable to add file, some errors occur!"
                    ]
                ], 400);
            }
        }
    }
}
