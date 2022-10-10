<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PayoutController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $key
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $key)
    {
        // set up query options
        $query_order = 'date';
        $query_sort = 'desc';
        $query_paginate = 30;

        if($request->exists('order')) {
            if($request->order == 'month_name') {
                $query_order = 'date';
            }
            else {
                $query_order = $request->order;
            }
        }
        if($request->exists('sort')) {
            $query_sort = $request->sort;
        }
        if($request->exists('paginate')) {
            $query_paginate = $request->paginate;
        }

        try{
            // result
            $result = DB::table('quin_monthly_payout')->select(
                'payout_id',
                'quin_monthly_payout.partner_id',
                'date',
                DB::raw('date_format(date, "%M %Y") as month_name'),
                'amount',
                DB::raw('
                    (case when '.DB::getTablePrefix().'quin_monthly_payout.status = 0 then "Failed"
                        when '.DB::getTablePrefix().'quin_monthly_payout.status = 1 then "Pending"
                        when '.DB::getTablePrefix().'quin_monthly_payout.status = 2 then "Processing"
                        when '.DB::getTablePrefix().'quin_monthly_payout.status = 3 then "Paid"
                        else '.DB::getTablePrefix().'quin_monthly_payout.status
                    end) as status
                ')
            )
            ->join('quin_users_meta', 'quin_monthly_payout.partner_id', '=', 'quin_users_meta.referral_code')
            ->where('quin_users_meta.users_key', $key)
            ->orderBy($query_order, $query_sort)
            ->paginate($query_paginate);

            return response([
                "records" => $result
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex){
            return response([
                "error" => [
                    "code"=>"PO001",
                    "message"=>"Resource not found!"
                ]
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
