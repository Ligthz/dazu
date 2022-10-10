<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\QuinUser;
use Carbon\Carbon;

class MonthlyTargetController extends Controller
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($key)
    {
        $date = formatDateTimeZone(Carbon::now(), 1);

        $start_date = date_format(date_create($date), "Y-m-01");
        $end_date = date_format(date_create($date), "Y-m-d");

        // monthly requirement
        $requirement = DB::table('quin_daily_sales')->select(
            DB::raw(DB::getTablePrefix().'quin_daily_sales.referral_code'),
            DB::raw("max(".DB::getTablePrefix()."quin_daily_sales.date) as date"),
            DB::raw("sum(coalesce(".DB::getTablePrefix()."quin_daily_sales.personal_sales, 0) + coalesce(".DB::getTablePrefix()."quin_daily_sales.referral_sales, 0)) as personal_sales"),
            DB::raw(DB::getTablePrefix().'quin_options.option_value as maintain_amount')
        )
        ->join('quin_options', function($join) {
            $join->where('option_name', '=', 'monthly_kpi');
        })
        ->where('quin_daily_sales.users_key', '=', $key)
        ->where('quin_daily_sales.date', '>=', $start_date)
        ->where('quin_daily_sales.date', '<', $end_date)
        ->groupBy('quin_daily_sales.referral_code', 'maintain_amount')
        ->first();

        return response([
            'result' => $requirement
        ], 200);
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
