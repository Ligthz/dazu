<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\QuinRolesHistory;
use App\Models\QuinUser;

class SalesController extends Controller
{
    /**
     * Display the specified resource.
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function personalSalesChart (Request $request, $key)
    {
        $rules = [
            'startDate' => 'required',
            'endDate' => 'required',
            'option' => 'required|numeric'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            //TODO Handle your error
            return response([
                "error" => [
                    "code" => "PSC001",
                    "message" => "Required field(s) missing!"
                ]
            ], 400);
        }

        try{
            $startDate = date_create($request->startDate);
            $endDate = date_create($request->endDate);

            $diff = date_diff($startDate, $endDate);
        }
        catch (Exception $e) {
            return response([
                "error" => [
                    "code" => "PSC002",
                    "message" => "Invalid date input!"
                ]
            ], 400);
        }

        if((int) $diff->format("%R%a") < 0) {
            // Invalid date range
            return response([
                "error" => [
                    "code" => "PSC003",
                    "message" => "Invalid date range!"
                ]
            ], 400);
        }

        // calculate compared past date
        $compared_to_end_date = date_create($request->startDate);
        date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
        $compared_to_end_date->setTime(23, 59, 59);

        if((int) $request->option == 0) {
            $compared_to_start_date = date_format($compared_to_end_date, "Y-m-d 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if((int) $request->option == 1) {
            $compared_to_start_date = clone $compared_to_end_date;
            date_sub($compared_to_start_date, date_interval_create_from_date_string("6 days"));

            $compared_to_start_date = date_format($compared_to_start_date, "Y-m-d 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if((int) $request->option == 2) {
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
            $compared_to_start_date = date('Y-m-01 00:00:00', strtotime($compared_to_end_date));
        }
        else if ((int) $request->option == 3) {
            $compared_to_start_date = clone $compared_to_end_date;
            date_sub($compared_to_start_date, date_interval_create_from_date_string("2 months"));

            $compared_to_start_date = date_format($compared_to_start_date, "Y-m-01 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if ((int) $request->option == 4) {
            $compared_to_start_date = clone $compared_to_end_date;
            date_sub($compared_to_start_date, date_interval_create_from_date_string("5 months"));

            $compared_to_start_date = date_format($compared_to_start_date, "Y-m-01 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if ((int) $request->option == 5) {
            $compared_to_start_date = clone $compared_to_end_date;
            date_sub($compared_to_start_date, date_interval_create_from_date_string("11 months"));

            $compared_to_start_date = date_format($compared_to_start_date, "Y-m-01 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else {
            $compared_to_start_date = clone $compared_to_end_date;
            date_sub($compared_to_start_date, date_interval_create_from_date_string($diff->format("%a days")));

            $compared_to_start_date = date_format($compared_to_start_date, "Y-m-d 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }

        /*
         *  Sales - Chart
        */
        if((int) $request->option == 0) {
            // today
            // time

            // current result
            $subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("DATE_FORMAT(".DB::getTablePrefix()."quin_order_item_meta.date_created, '%H:00:00') as purchased_date"),
                DB::raw('ROUND(SUM('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 2) as accumulate_sales')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = quin_users_meta.users_id OR quin_order_item_meta.sold_by = quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $request->startDate)
            ->where('quin_order_item_meta.date_created', '<=', $request->endDate)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or wc_order_stats.status = 'wc-shipping' or wc_order_stats.status = 'wc-completed' or wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $result = DB::table(DB::raw('('.$subQuery->toSql().') as personal_sales'))->select(
                DB::raw('time_list.times as dates'),
                DB::raw('COALESCE(personal_sales.accumulate_sales, 0) as accumulate_sales')
            )
            ->rightJoin(DB::raw("
                (select DATE_FORMAT(selected_date,'%H:%i:%s') as times
                from
                    (select addtime('1990-01-01 00:00:00', (t1.i*10 + t0.i)*10000) selected_date
                    from
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                        (select 0 i union select 1 union select 2) t1) as v
                    where selected_date between '1990-01-01 00:00:00' and '1990-01-01 23:59:59'
                    order by selected_date) as time_list"
                ), 'personal_sales.purchased_date', '=', 'time_list.times'
            )
            ->orderBy('time_list.times', 'asc')
            ->mergeBindings($subQuery);


            // past result
            $past_subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("DATE_FORMAT(".DB::getTablePrefix()."quin_order_item_meta.date_created, '%H:00:00') as purchased_date"),
                DB::raw('ROUND(SUM('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 2) as accumulate_sales')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = quin_users_meta.users_id OR quin_order_item_meta.sold_by = quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or wc_order_stats.status = 'wc-shipping' or wc_order_stats.status = 'wc-completed' or wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as personal_sales'))->select(
                DB::raw('time_list.times as dates'),
                DB::raw('COALESCE(personal_sales.accumulate_sales, 0) as accumulate_sales')
            )
            ->rightJoin(DB::raw("
                (select DATE_FORMAT(selected_date,'%H:%i:%s') as times
                from
                    (select addtime('1990-01-01 00:00:00', (t1.i*10 + t0.i)*10000) selected_date
                    from
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                        (select 0 i union select 1 union select 2) t1) as v
                    where selected_date between '1990-01-01 00:00:00' and '1990-01-01 23:59:59'
                    order by selected_date) as time_list"
                ), 'personal_sales.purchased_date', '=', 'time_list.times'
            )
            ->orderBy('time_list.times', 'asc')
            ->mergeBindings($past_subQuery);
        }
        else if((int) $request->option == 1 || (int) $request->option == 2 || (int) $request->option == 3)
        {
            // last 7 days or last month or last 3 months
            // day

            // current result
            $subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("CAST(".DB::getTablePrefix()."quin_order_item_meta.date_created AS DATE) as purchased_date"),
                DB::raw('ROUND(SUM('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 2) as accumulate_sales')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = quin_users_meta.users_id OR quin_order_item_meta.sold_by = quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $request->startDate)
            ->where('quin_order_item_meta.date_created', '<=', $request->endDate)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or wc_order_stats.status = 'wc-shipping' or wc_order_stats.status = 'wc-completed' or wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $result = DB::table(DB::raw('('.$subQuery->toSql().') as personal_sales'))->select(
                DB::raw('date_list.dates as dates'),
                DB::raw('COALESCE(personal_sales.accumulate_sales, 0) as accumulate_sales')
            )
            ->rightJoin(DB::raw("
                (select dates from
                (select adddate('2021-01-01', t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t2,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6) t3,
                        (select 0 i union select 1 union select 2 union select 3) t4) v
                where CAST(dates AS DATE) between '$request->startDate' and '$request->endDate') as date_list"
                ), 'personal_sales.purchased_date', '=', 'date_list.dates'
            )
            ->orderBy('date_list.dates', 'asc')
            ->mergeBindings($subQuery);



            // past result
            $past_subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("CAST(".DB::getTablePrefix()."quin_order_item_meta.date_created AS DATE) as purchased_date"),
                DB::raw('ROUND(SUM('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 2) as accumulate_sales')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = quin_users_meta.users_id OR quin_order_item_meta.sold_by = quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or wc_order_stats.status = 'wc-shipping' or wc_order_stats.status = 'wc-completed' or wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as personal_sales'))->select(
                DB::raw('date_list.dates as dates'),
                DB::raw('COALESCE(personal_sales.accumulate_sales, 0) as accumulate_sales')
            )
            ->rightJoin(DB::raw("
                (select dates from
                (select adddate('2021-01-01', t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t2,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6) t3,
                        (select 0 i union select 1 union select 2 union select 3) t4) v
                where CAST(dates AS DATE) between '$compared_to_start_date' and '$compared_to_end_date') as date_list"
                ), 'personal_sales.purchased_date', '=', 'date_list.dates'
            )
            ->orderBy('date_list.dates', 'asc')
            ->mergeBindings($past_subQuery);

        }
        else if ((int) $request->option == 4 || (int) $request->option == 5) {
            // last 6 months or last 12 months
            // month

            // current result
            $subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("DATE_FORMAT(".DB::getTablePrefix()."quin_order_item_meta.date_created, '%Y-%m') as purchased_date"),
                DB::raw('ROUND(SUM('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 2) as accumulate_sales')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = quin_users_meta.users_id OR quin_order_item_meta.sold_by = quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $request->startDate)
            ->where('quin_order_item_meta.date_created', '<=', $request->endDate)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or wc_order_stats.status = 'wc-shipping' or wc_order_stats.status = 'wc-completed' or wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $result = DB::table(DB::raw('('.$subQuery->toSql().') as personal_sales'))->select(
                DB::raw('month_list.months as dates'),
                DB::raw('COALESCE(personal_sales.accumulate_sales, 0) as accumulate_sales')
            )
            ->rightJoin(DB::raw("
                (select DATE_FORMAT(dates, '%Y-%m') as months
                from
                (select adddate('2019-09-01', INTERVAL (t1.i*10 + t0.i) MONTH) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                    (select 0 i union select 1 union select 2) t1) as v
                where DATE_FORMAT(dates, '%Y-%m-%d 00:00:00') between '$request->startDate' and '$request->endDate'
                order by dates) as month_list"
                ), 'personal_sales.purchased_date', '=', 'month_list.months'
            )
            ->orderBy('month_list.months', 'asc')
            ->mergeBindings($subQuery);



            // // past result
            $past_subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("DATE_FORMAT(".DB::getTablePrefix()."quin_order_item_meta.date_created, '%Y-%m') as purchased_date"),
                DB::raw('ROUND(SUM('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 2) as accumulate_sales')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = quin_users_meta.users_id OR quin_order_item_meta.sold_by = quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or wc_order_stats.status = 'wc-shipping' or wc_order_stats.status = 'wc-completed' or wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as personal_sales'))->select(
                DB::raw('month_list.months as dates'),
                DB::raw('COALESCE(personal_sales.accumulate_sales, 0) as accumulate_sales')
            )
            ->rightJoin(DB::raw("
                (select DATE_FORMAT(dates, '%Y-%m') as months
                from
                (select adddate('2019-09-01', INTERVAL (t1.i*10 + t0.i) MONTH) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                    (select 0 i union select 1 union select 2) t1) as v
                where DATE_FORMAT(dates, '%Y-%m-%d 00:00:00') between '$compared_to_start_date' and '$compared_to_end_date'
                order by dates) as month_list"
                ), 'personal_sales.purchased_date', '=', 'month_list.months'
            )
            ->orderBy('month_list.months', 'asc')
            ->mergeBindings($past_subQuery);
        }
        else {
            // self-defined
            // day

            // current result
            $subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("CAST(".DB::getTablePrefix()."quin_order_item_meta.date_created AS DATE) as purchased_date"),
                DB::raw('ROUND(SUM('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 2) as accumulate_sales')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = quin_users_meta.users_id OR quin_order_item_meta.sold_by = quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $request->startDate)
            ->where('quin_order_item_meta.date_created', '<=', $request->endDate)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or wc_order_stats.status = 'wc-shipping' or wc_order_stats.status = 'wc-completed' or wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $result = DB::table(DB::raw('('.$subQuery->toSql().') as personal_sales'))->select(
                DB::raw('date_list.dates as dates'),
                DB::raw('COALESCE(personal_sales.accumulate_sales, 0) as accumulate_sales')
            )
            ->rightJoin(DB::raw("
                (select dates from
                (select adddate('2021-01-01', t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t2,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6) t3,
                        (select 0 i union select 1 union select 2 union select 3) t4) v
                where CAST(dates AS DATE) between '$request->startDate' and '$request->endDate') as date_list"
                ), 'personal_sales.purchased_date', '=', 'date_list.dates'
            )
            ->orderBy('date_list.dates', 'asc')
            ->mergeBindings($subQuery);



            // past result
            $past_subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("CAST(".DB::getTablePrefix()."quin_order_item_meta.date_created AS DATE) as purchased_date"),
                DB::raw('ROUND(SUM(".DB::getTablePrefix()."quin_order_item_meta.product_subtotal), 2) as accumulate_sales')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = quin_users_meta.users_id OR quin_order_item_meta.sold_by = quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or wc_order_stats.status = 'wc-shipping' or wc_order_stats.status = 'wc-completed' or wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as personal_sales'))->select(
                DB::raw('date_list.dates as dates'),
                DB::raw('COALESCE(personal_sales.accumulate_sales, 0) as accumulate_sales')
            )
            ->rightJoin(DB::raw("
                (select dates from
                (select adddate('2021-01-01', t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t2,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6) t3,
                        (select 0 i union select 1 union select 2 union select 3) t4) v
                where CAST(dates AS DATE) between '$compared_to_start_date' and '$compared_to_end_date') as date_list"
                ), 'personal_sales.purchased_date', '=', 'date_list.dates'
            )
            ->orderBy('date_list.dates', 'asc')
            ->mergeBindings($past_subQuery);
        }

        $result = $result->get();
        $past_result = $past_result->get();

        return response([
            'current_records' => $result,
            'past_records' => $past_result
        ], 200);
    }
}
