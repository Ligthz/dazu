<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReferralController extends Controller
{
    /**
     * Display the specified resource.
     * @param  \Illuminate\Http\Request  $request
     *
     * @param  int  $key
     * @return \Illuminate\Http\Response
     */
    public function index (Request $request, $key)
    {
        $date = formatDateTimeZone(Carbon::now(), 1);

        if((int) $request->o == 0) {
            $start_date = date_format(date_create($date), "Y-m-d 00:00:00");
            $end_date = date_format(date_create($date), "Y-m-d H:i:s");
        }
        else if((int) $request->o == 1) {
            $start_date = date_create($date);
            date_sub($start_date, date_interval_create_from_date_string("6 days"));
            $start_date = date_format($start_date, "Y-m-d 00:00:00");

            $end_date = date_format(date_create($date), "Y-m-d H:i:s");
        }
        else if((int) $request->o == 2) {
            $start_date = date_format(date_create($date), "Y-m-01 00:00:00");
            $end_date = date_format(date_create($date), "Y-m-d H:i:s");
        }
        else if((int) $request->o == 3) {
            $start_date = date_create($date);
            date_sub($start_date, date_interval_create_from_date_string("3 months"));
            $start_date = date_format($start_date, "Y-m-d 00:00:00");

            $end_date = date_format(date_create($date), "Y-m-d H:i:s");
        }
        else if((int) $request->o == 4) {
            $start_date = date_create($date);
            date_sub($start_date, date_interval_create_from_date_string("6 months"));
            $start_date = date_format($start_date, "Y-m-d 00:00:00");

            $end_date = date_format(date_create($date), "Y-m-d H:i:s");
        }
        else if((int) $request->o == 5) {
            $start_date = date_create($date);
            date_sub($start_date, date_interval_create_from_date_string("12 months"));
            $start_date = date_format($start_date, "Y-m-d 00:00:00");

            $end_date = date_format(date_create($date), "Y-m-d H:i:s");
        }
        else {
            $start_date = date_format(date_create($date), "Y-m-d 00:00:00");
            $end_date = date_format(date_create($date), "Y-m-d H:i:s");
        }


        // set up query options
        $query_order = 'date_created';
        $query_sort = 'desc';
        $query_paginate = 30;
        $query_search = '%%';

        if($request->exists('order')) {
            $query_order = $request->order;
        }
        if($request->exists('sort')) {
            $query_sort = $request->sort;
        }
        if($request->exists('paginate')) {
            $query_paginate = $request->paginate;
        }
        if($request->exists('search')) {
            $query_search = '%' . $request->search . '%';
        }


        // result
        $result = DB::table('quin_order_item_meta')->select(
            DB::raw('DISTINCT '.DB::getTablePrefix().'orders_sales.order_id'),
            'orders_sales.date_created',
            DB::raw('CONCAT('.DB::getTablePrefix().'quin_users_meta.fname, " ", '.DB::getTablePrefix().'quin_users_meta.lname) as customer_name'),
            DB::raw(DB::getTablePrefix().'quin_users_meta.contact as customer_phone'),
            'orders_sales.sales',
            'orders_sales.status'
        )
        ->join(DB::raw("
            (SELECT DISTINCT ".DB::getTablePrefix()."wc_order_stats.order_id,
            ".DB::getTablePrefix()."quin_order_item_meta.date_created,
            COALESCE(ROUND(SUM(".DB::getTablePrefix()."quin_order_item_meta.product_subtotal)), 0) AS sales,
            (case
                when ".DB::getTablePrefix()."wc_order_stats.status = 'wc-pending' then 'Unpaid'
                when ".DB::getTablePrefix()."wc_order_stats.status = 'wc-on-hold' then 'Unpaid'
                when ".DB::getTablePrefix()."wc_order_stats.status = 'wc-failed' then 'Payment Failed'
                when ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' then 'Order Shipped'
                when ".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' then 'Payment Success'
                when ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' then 'Order Completed'
                when ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered' then 'Order Delivered'
                when ".DB::getTablePrefix()."wc_order_stats.status = 'wc-refunded' then 'Order Refunded'
                when ".DB::getTablePrefix()."wc_order_stats.status = 'wc-cancelled' then 'Order Cancelled'
                else ".DB::getTablePrefix()."wc_order_stats.status
            end) as status
            FROM ".DB::getTablePrefix()."wc_order_stats
            inner join ".DB::getTablePrefix()."quin_order_item_meta on ".DB::getTablePrefix()."wc_order_stats.order_id = ".DB::getTablePrefix()."quin_order_item_meta.order_id
            inner join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_order_item_meta.date_created > ".DB::getTablePrefix()."quin_users_meta.partner_joined_at and (".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)
            WHERE ".DB::getTablePrefix()."quin_users_meta.users_key = '$key'
                AND ".DB::getTablePrefix()."quin_order_item_meta.date_created >= '$start_date'
                AND ".DB::getTablePrefix()."quin_order_item_meta.date_created <= '$end_date'
            group by ".DB::getTablePrefix()."wc_order_stats.order_id, ".DB::getTablePrefix()."quin_order_item_meta.date_created, status
            order by ".DB::getTablePrefix()."wc_order_stats.order_id asc) as ".DB::getTablePrefix()."orders_sales"
            ), 'quin_order_item_meta.order_id', '=', 'orders_sales.order_id'
        )
        ->join('quin_users_meta', 'quin_order_item_meta.customer_id', '=', 'quin_users_meta.users_id')
        ->join('users', 'quin_users_meta.users_id', '=', 'users.ID')
        ->where('orders_sales.order_id', 'LIKE', $query_search)
        ->orWhere('orders_sales.date_created', 'LIKE', $query_search)
        ->orWhere('users.display_name', 'LIKE', $query_search)
        ->orWhere('quin_users_meta.contact', 'LIKE', $query_search)
        ->orWhere('orders_sales.sales', 'LIKE', $query_search)
        ->orWhere('orders_sales.status', 'LIKE', $query_search)
        ->orderBy($query_order, $query_sort)
        ->paginate($query_paginate);

        return response([
            "records" => $result
        ], 200);
    }


    /**
     * Display the specified resource.
     * @param  \Illuminate\Http\Request  $request
     *
     * @param  int  $key
     * @return \Illuminate\Http\Response
     */
    public function salesChart(Request $request, $key)
    {

        $date = formatDateTimeZone(Carbon::now(), 1);

        if((int) $request->o == 0) {
            $start_date = date_format(date_create($date), "Y-m-d 00:00:00");
            $end_date = date_format(date_create($date), "Y-m-d H:i:s");

            $compared_to_end_date = date_create($start_date);
            date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
            $compared_to_end_date->setTime(23, 59, 59);

            $compared_to_start_date = date_format($compared_to_end_date, "Y-m-d 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if((int) $request->o == 1) {
            $start_date = date_create($date);
            date_sub($start_date, date_interval_create_from_date_string("6 days"));
            $start_date = date_format($start_date, "Y-m-d 00:00:00");

            $end_date = date_format(date_create($date), "Y-m-d H:i:s");


            $compared_to_end_date = date_create($start_date);
            date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
            $compared_to_end_date->setTime(23, 59, 59);

            $compared_to_start_date = clone $compared_to_end_date;
            date_sub($compared_to_start_date, date_interval_create_from_date_string("6 days"));

            $compared_to_start_date = date_format($compared_to_start_date, "Y-m-d 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if((int) $request->o == 2) {
            $start_date = date_format(date_create($date), "Y-m-01 00:00:00");
            $end_date = date_format(date_create($date), "Y-m-d H:i:s");

            $compared_to_end_date = date_create($start_date);
            date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
            $compared_to_end_date->setTime(23, 59, 59);

            $compared_to_start_date = date_format($compared_to_end_date, "Y-m-01 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if((int) $request->o == 3) {
            $start_date = date_create($date);
            date_sub($start_date, date_interval_create_from_date_string("3 months"));
            $start_date = date_format($start_date, "Y-m-d 00:00:00");

            $end_date = date_format(date_create($date), "Y-m-d H:i:s");


            $compared_to_end_date = date_create($start_date);
            date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
            $compared_to_end_date->setTime(23, 59, 59);

            $compared_to_start_date = clone $compared_to_end_date;
            date_sub($compared_to_start_date, date_interval_create_from_date_string("3 months"));

            $compared_to_start_date = date_format($compared_to_start_date, "Y-m-d 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if((int) $request->o == 4) {
            $start_date = date_create($date);
            date_sub($start_date, date_interval_create_from_date_string("6 months"));
            $start_date = date_format($start_date, "Y-m-d 00:00:00");

            $end_date = date_format(date_create($date), "Y-m-d H:i:s");


            $compared_to_end_date = date_create($start_date);
            date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
            $compared_to_end_date->setTime(23, 59, 59);

            $compared_to_start_date = clone $compared_to_end_date;
            date_sub($compared_to_start_date, date_interval_create_from_date_string("6 months"));

            $compared_to_start_date = date_format($compared_to_start_date, "Y-m-d 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if((int) $request->o == 5) {
            $start_date = date_create($date);
            date_sub($start_date, date_interval_create_from_date_string("12 months"));
            $start_date = date_format($start_date, "Y-m-d 00:00:00");

            $end_date = date_format(date_create($date), "Y-m-d H:i:s");


            $compared_to_end_date = date_create($start_date);
            date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
            $compared_to_end_date->setTime(23, 59, 59);

            $compared_to_start_date = clone $compared_to_end_date;
            date_sub($compared_to_start_date, date_interval_create_from_date_string("12 months"));

            $compared_to_start_date = date_format($compared_to_start_date, "Y-m-d 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else {
            $start_date = date_format(date_create($date), "Y-m-d 00:00:00");
            $end_date = date_format(date_create($date), "Y-m-d H:i:s");

            $compared_to_start_date = date_format(date_create($date), "Y-m-d 00:00:00");
            $compared_to_end_date = date_format(date_create($date), "Y-m-d H:i:s");
        }

        /*
        *  Sales - Chart
        */
        if((int) $request->o == 0) {
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
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $start_date)
            ->where('quin_order_item_meta.date_created', '<=', $end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $result = DB::table(DB::raw('('.$subQuery->toSql().') as '.DB::getTablePrefix().'personal_sales'))->select(
                DB::raw(DB::getTablePrefix().'time_list.times as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'personal_sales.accumulate_sales, 0) as accumulate_sales')
            )
            ->rightJoin(DB::raw("
                (select DATE_FORMAT(selected_date,'%H:%i:%s') as times
                from
                    (select addtime('1990-01-01 00:00:00', (t1.i*10 + t0.i)*10000) selected_date
                    from
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                        (select 0 i union select 1 union select 2) t1) as v
                    where selected_date between '1990-01-01 00:00:00' and '1990-01-01 23:59:59'
                    order by selected_date) as ".DB::getTablePrefix()."time_list"
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
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as '.DB::getTablePrefix().'personal_sales'))->select(
                DB::raw(DB::getTablePrefix().'time_list.times as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'personal_sales.accumulate_sales, 0) as accumulate_sales')
            )
            ->rightJoin(DB::raw("
                (select DATE_FORMAT(selected_date,'%H:%i:%s') as times
                from
                    (select addtime('1990-01-01 00:00:00', (t1.i*10 + t0.i)*10000) selected_date
                    from
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                        (select 0 i union select 1 union select 2) t1) as v
                    where selected_date between '1990-01-01 00:00:00' and '1990-01-01 23:59:59'
                    order by selected_date) as ".DB::getTablePrefix()."time_list"
                ), 'personal_sales.purchased_date', '=', 'time_list.times'
            )
            ->orderBy('time_list.times', 'asc')
            ->mergeBindings($past_subQuery);
        }
        else if((int) $request->o == 1 || (int) $request->o == 2 || (int) $request->o == 3)
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
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $start_date)
            ->where('quin_order_item_meta.date_created', '<=', $end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $result = DB::table(DB::raw('('.$subQuery->toSql().') as '.DB::getTablePrefix().'personal_sales'))->select(
                DB::raw(DB::getTablePrefix().'date_list.dates as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'personal_sales.accumulate_sales, 0) as accumulate_sales')
            )
            ->rightJoin(DB::raw("
                (select dates from
                (select adddate('2021-01-01', t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t2,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6) t3,
                        (select 0 i union select 1 union select 2 union select 3) t4) v
                where CAST(dates AS DATE) between '$start_date' and '$end_date') as ".DB::getTablePrefix()."date_list"
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
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as '.DB::getTablePrefix().'personal_sales'))->select(
                DB::raw(DB::getTablePrefix().'date_list.dates as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'personal_sales.accumulate_sales, 0) as accumulate_sales')
            )
            ->rightJoin(DB::raw("
                (select dates from
                (select adddate('2021-01-01', t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t2,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6) t3,
                        (select 0 i union select 1 union select 2 union select 3) t4) v
                where CAST(dates AS DATE) between '$compared_to_start_date' and '$compared_to_end_date') as ".DB::getTablePrefix()."date_list"
                ), 'personal_sales.purchased_date', '=', 'date_list.dates'
            )
            ->orderBy('date_list.dates', 'asc')
            ->mergeBindings($past_subQuery);

        }
        else if ((int) $request->o == 4 || (int) $request->o == 5) {
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
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $start_date)
            ->where('quin_order_item_meta.date_created', '<=', $end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $result = DB::table(DB::raw('('.$subQuery->toSql().') as '.DB::getTablePrefix().'personal_sales'))->select(
                DB::raw(DB::getTablePrefix().'month_list.months as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'personal_sales.accumulate_sales, 0) as accumulate_sales')
            )
            ->rightJoin(DB::raw("
                (select DATE_FORMAT(dates, '%Y-%m') as months
                from
                (select adddate('2019-09-01', INTERVAL (t1.i*10 + t0.i) MONTH) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                    (select 0 i union select 1 union select 2) t1) as v
                where DATE_FORMAT(dates, '%Y-%m-%d 00:00:00') between '$start_date' and '$end_date'
                order by dates) as ".DB::getTablePrefix()."month_list"
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
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as '.DB::getTablePrefix().'personal_sales'))->select(
                DB::raw(DB::getTablePrefix().'month_list.months as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'personal_sales.accumulate_sales, 0) as accumulate_sales')
            )
            ->rightJoin(DB::raw("
                (select DATE_FORMAT(dates, '%Y-%m') as months
                from
                (select adddate('2019-09-01', INTERVAL (t1.i*10 + t0.i) MONTH) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                    (select 0 i union select 1 union select 2) t1) as v
                where DATE_FORMAT(dates, '%Y-%m-%d 00:00:00') between '$compared_to_start_date' and '$compared_to_end_date'
                order by dates) as ".DB::getTablePrefix()."month_list"
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
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $start_date)
            ->where('quin_order_item_meta.date_created', '<=', $end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $result = DB::table(DB::raw('('.$subQuery->toSql().') as '.DB::getTablePrefix().'personal_sales'))->select(
                DB::raw(DB::getTablePrefix().'date_list.dates as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'personal_sales.accumulate_sales, 0) as accumulate_sales')
            )
            ->rightJoin(DB::raw("
                (select dates from
                (select adddate('2021-01-01', t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t2,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6) t3,
                        (select 0 i union select 1 union select 2 union select 3) t4) v
                where CAST(dates AS DATE) between '$start_date' and '$end_date') as ".DB::getTablePrefix()."date_list"
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
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as '.DB::getTablePrefix().'personal_sales'))->select(
                DB::raw(DB::getTablePrefix().'date_list.dates as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'personal_sales.accumulate_sales, 0) as accumulate_sales')
            )
            ->rightJoin(DB::raw("
                (select dates from
                (select adddate('2021-01-01', t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t2,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6) t3,
                        (select 0 i union select 1 union select 2 union select 3) t4) v
                where CAST(dates AS DATE) between '$compared_to_start_date' and '$compared_to_end_date') as ".DB::getTablePrefix()."date_list"
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
     * @param  \Illuminate\Http\Request  $request
     *
     * @param  int  $key
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $key)
    {

        $date = formatDateTimeZone(Carbon::now(), 1);

        if((int) $request->o == 0) {
            $start_date = date_format(date_create($date), "Y-m-d 00:00:00");
            $end_date = date_format(date_create($date), "Y-m-d H:i:s");

            $compared_to_end_date = date_create($start_date);
            date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
            $compared_to_end_date->setTime(23, 59, 59);

            $compared_to_start_date = date_format($compared_to_end_date, "Y-m-d 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if((int) $request->o == 1) {
            $start_date = date_create($date);
            date_sub($start_date, date_interval_create_from_date_string("6 days"));
            $start_date = date_format($start_date, "Y-m-d 00:00:00");

            $end_date = date_format(date_create($date), "Y-m-d H:i:s");


            $compared_to_end_date = date_create($start_date);
            date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
            $compared_to_end_date->setTime(23, 59, 59);

            $compared_to_start_date = clone $compared_to_end_date;
            date_sub($compared_to_start_date, date_interval_create_from_date_string("6 days"));

            $compared_to_start_date = date_format($compared_to_start_date, "Y-m-d 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if((int) $request->o == 2) {
            $start_date = date_format(date_create($date), "Y-m-01 00:00:00");
            $end_date = date_format(date_create($date), "Y-m-d H:i:s");

            $compared_to_end_date = date_create($start_date);
            date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
            $compared_to_end_date->setTime(23, 59, 59);

            $compared_to_start_date = date_format($compared_to_end_date, "Y-m-01 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if((int) $request->o == 3) {
            $start_date = date_create($date);
            date_sub($start_date, date_interval_create_from_date_string("3 months"));
            $start_date = date_format($start_date, "Y-m-d 00:00:00");

            $end_date = date_format(date_create($date), "Y-m-d H:i:s");


            $compared_to_end_date = date_create($start_date);
            date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
            $compared_to_end_date->setTime(23, 59, 59);

            $compared_to_start_date = clone $compared_to_end_date;
            date_sub($compared_to_start_date, date_interval_create_from_date_string("3 months"));

            $compared_to_start_date = date_format($compared_to_start_date, "Y-m-d 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if((int) $request->o == 4) {
            $start_date = date_create($date);
            date_sub($start_date, date_interval_create_from_date_string("6 months"));
            $start_date = date_format($start_date, "Y-m-d 00:00:00");

            $end_date = date_format(date_create($date), "Y-m-d H:i:s");


            $compared_to_end_date = date_create($start_date);
            date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
            $compared_to_end_date->setTime(23, 59, 59);

            $compared_to_start_date = clone $compared_to_end_date;
            date_sub($compared_to_start_date, date_interval_create_from_date_string("6 months"));

            $compared_to_start_date = date_format($compared_to_start_date, "Y-m-d 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if((int) $request->o == 5) {
            $start_date = date_create($date);
            date_sub($start_date, date_interval_create_from_date_string("12 months"));
            $start_date = date_format($start_date, "Y-m-d 00:00:00");

            $end_date = date_format(date_create($date), "Y-m-d H:i:s");


            $compared_to_end_date = date_create($start_date);
            date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
            $compared_to_end_date->setTime(23, 59, 59);

            $compared_to_start_date = clone $compared_to_end_date;
            date_sub($compared_to_start_date, date_interval_create_from_date_string("12 months"));

            $compared_to_start_date = date_format($compared_to_start_date, "Y-m-d 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else {
            $start_date = date_format(date_create($date), "Y-m-d 00:00:00");
            $end_date = date_format(date_create($date), "Y-m-d H:i:s");

            $compared_to_start_date = date_format(date_create($date), "Y-m-d 00:00:00");
            $compared_to_end_date = date_format(date_create($date), "Y-m-d H:i:s");
        }

        // return array(
        //     "start" => $start_date,
        //     "end" => $end_date,
        //     "com_start" => $compared_to_start_date,
        //     "com_end" => $compared_to_end_date
        // );


        /* Personal Sales - Number */

        // current period
        $current_sales = DB::table('quin_order_item_meta')->select(
            DB::raw('COALESCE(SUM('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 0) as current_sales')
        )
        ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
        ->join('quin_users_meta', function ($join) {
            $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
        })
        ->where('quin_users_meta.users_key', $key)
        ->where('quin_order_item_meta.date_created', '>=', $start_date)
        ->where('quin_order_item_meta.date_created', '<=', $end_date)
        ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
        ->get();


        // compared to last period
        $past_sales = DB::table('quin_order_item_meta')->select(
            DB::raw('COALESCE(ROUND(SUM('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal)), 0) as past_sales')
        )
        ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
        ->join('quin_users_meta', function ($join) {
            $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
        })
        ->where('quin_users_meta.users_key', $key)
        ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
        ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
        ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
        ->get();



        $current_sales = (float) $current_sales[0]->current_sales;
        $past_sales = (float) $past_sales[0]->past_sales;

        // calculate sales percentage
        if ($past_sales <= 0 && $current_sales <= 0) {
            $sales_percent = 0;
        }
        else if ($past_sales <= 0) {
            $sales_percent = 100;
        }
        else {
            $sales_percent = ($current_sales - $past_sales) / $past_sales * 100;
        }

        // determine is sales percentage increment or decrement
        if($sales_percent >= 0) {
            $sales_stat = 1;
        }
        else {
            $sales_stat = 2;
        }





        /* Personal Orders - Number */

        // current period
        $current_orders = DB::table('quin_order_item_meta')->select(
            DB::raw('COALESCE(COUNT(DISTINCT '.DB::getTablePrefix().'quin_order_item_meta.order_id), 0) as current_orders')
        )
        ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
        ->join('quin_users_meta', function ($join) {
            $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
        })
        ->where('quin_users_meta.users_key', $key)
        ->where('quin_order_item_meta.date_created', '>=', $start_date)
        ->where('quin_order_item_meta.date_created', '<=', $end_date)
        ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
        ->get();



        // compared to last period
        $past_orders = DB::table('quin_order_item_meta')->select(
            DB::raw('COALESCE(COUNT(DISTINCT '.DB::getTablePrefix().'quin_order_item_meta.order_id), 0) as past_orders')
        )
        ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
        ->join('quin_users_meta', function ($join) {
            $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
        })
        ->where('quin_users_meta.users_key', $key)
        ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
        ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
        ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
        ->get();

        $current_orders = (int) $current_orders[0]->current_orders;
        $past_orders = (int) $past_orders[0]->past_orders;

        // calculate sales percentage
        if ($past_orders <= 0 && $current_orders <= 0) {
            $orders_percent = 0;
        }
        else if ($past_orders <= 0) {
            $orders_percent = 100;
        }
        else {
            $orders_percent = ($current_orders - $past_orders) / $past_orders * 100;
        }

        // determine is sales percentage increment or decrement
        if($orders_percent >= 0) {
            $orders_stat = 1;
        }
        else {
            $orders_stat = 2;
        }

        return response([
            'current_sales' => $current_sales,
            'past_sales' => $past_sales,
            'sales_percentage' => abs(round($sales_percent, 0)) . '%',
            'sales_status' => $sales_stat,
            'current_orders' => $current_orders,
            'past_orders' => $past_orders,
            'orders_percentage' => abs(round($orders_percent, 0)) . '%',
            'orders_status' => $orders_stat
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


    /**
     * Display the specified resource.
     * @param  \Illuminate\Http\Request  $request
     *
     * @param  int  $key
     * @return \Illuminate\Http\Response
     */
    public function ordersChart (Request $request, $key)
    {
        $date = formatDateTimeZone(Carbon::now(), 1);

        if((int) $request->o == 0) {
            $start_date = date_format(date_create($date), "Y-m-d 00:00:00");
            $end_date = date_format(date_create($date), "Y-m-d H:i:s");

            $compared_to_end_date = date_create($start_date);
            date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
            $compared_to_end_date->setTime(23, 59, 59);

            $compared_to_start_date = date_format($compared_to_end_date, "Y-m-d 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if((int) $request->o == 1) {
            $start_date = date_create($date);
            date_sub($start_date, date_interval_create_from_date_string("6 days"));
            $start_date = date_format($start_date, "Y-m-d 00:00:00");

            $end_date = date_format(date_create($date), "Y-m-d H:i:s");


            $compared_to_end_date = date_create($start_date);
            date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
            $compared_to_end_date->setTime(23, 59, 59);

            $compared_to_start_date = clone $compared_to_end_date;
            date_sub($compared_to_start_date, date_interval_create_from_date_string("6 days"));

            $compared_to_start_date = date_format($compared_to_start_date, "Y-m-d 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if((int) $request->o == 2) {
            $start_date = date_format(date_create($date), "Y-m-01 00:00:00");
            $end_date = date_format(date_create($date), "Y-m-d H:i:s");

            $compared_to_end_date = date_create($start_date);
            date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
            $compared_to_end_date->setTime(23, 59, 59);

            $compared_to_start_date = date_format($compared_to_end_date, "Y-m-01 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if((int) $request->o == 3) {
            $start_date = date_create($date);
            date_sub($start_date, date_interval_create_from_date_string("3 months"));
            $start_date = date_format($start_date, "Y-m-d 00:00:00");

            $end_date = date_format(date_create($date), "Y-m-d H:i:s");


            $compared_to_end_date = date_create($start_date);
            date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
            $compared_to_end_date->setTime(23, 59, 59);

            $compared_to_start_date = clone $compared_to_end_date;
            date_sub($compared_to_start_date, date_interval_create_from_date_string("3 months"));

            $compared_to_start_date = date_format($compared_to_start_date, "Y-m-d 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if((int) $request->o == 4) {
            $start_date = date_create($date);
            date_sub($start_date, date_interval_create_from_date_string("6 months"));
            $start_date = date_format($start_date, "Y-m-d 00:00:00");

            $end_date = date_format(date_create($date), "Y-m-d H:i:s");


            $compared_to_end_date = date_create($start_date);
            date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
            $compared_to_end_date->setTime(23, 59, 59);

            $compared_to_start_date = clone $compared_to_end_date;
            date_sub($compared_to_start_date, date_interval_create_from_date_string("6 months"));

            $compared_to_start_date = date_format($compared_to_start_date, "Y-m-d 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else if((int) $request->o == 5) {
            $start_date = date_create($date);
            date_sub($start_date, date_interval_create_from_date_string("12 months"));
            $start_date = date_format($start_date, "Y-m-d 00:00:00");

            $end_date = date_format(date_create($date), "Y-m-d H:i:s");


            $compared_to_end_date = date_create($start_date);
            date_sub($compared_to_end_date, date_interval_create_from_date_string("1 day"));
            $compared_to_end_date->setTime(23, 59, 59);

            $compared_to_start_date = clone $compared_to_end_date;
            date_sub($compared_to_start_date, date_interval_create_from_date_string("12 months"));

            $compared_to_start_date = date_format($compared_to_start_date, "Y-m-d 00:00:00");
            $compared_to_end_date = date_format($compared_to_end_date, "Y-m-d H:i:s");
        }
        else {
            $start_date = date_format(date_create($date), "Y-m-d 00:00:00");
            $end_date = date_format(date_create($date), "Y-m-d H:i:s");

            $compared_to_start_date = date_format(date_create($date), "Y-m-d 00:00:00");
            $compared_to_end_date = date_format(date_create($date), "Y-m-d H:i:s");
        }

        // return array(
        //     "start" => $start_date,
        //     "end" => $end_date,
        //     "com_start" => $compared_to_start_date,
        //     "com_end" => $compared_to_end_date
        // );


        /*
         *  Orders - Chart
        */
        if((int) $request->o == 0) {
            // today
            // time

            // current result
            $subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("DATE_FORMAT(".DB::getTablePrefix()."quin_order_item_meta.date_created, '%H:00:00') as purchased_date"),
                DB::raw('COALESCE(COUNT(DISTINCT '.DB::getTablePrefix().'wc_order_stats.order_id), 0) as accumulate_orders')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $start_date)
            ->where('quin_order_item_meta.date_created', '<=', $end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $result = DB::table(DB::raw('('.$subQuery->toSql().') as '.DB::getTablePrefix().'personal_orders'))->select(
                DB::raw(DB::getTablePrefix().'time_list.times as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'personal_orders.accumulate_orders, 0) as accumulate_orders')
            )
            ->rightJoin(DB::raw("
                (select DATE_FORMAT(selected_date,'%H:%i:%s') as times
                from
                    (select addtime('1990-01-01 00:00:00', (t1.i*10 + t0.i)*10000) selected_date
                    from
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                        (select 0 i union select 1 union select 2) t1) as v
                    where selected_date between '1990-01-01 00:00:00' and '1990-01-01 23:59:59'
                    order by selected_date) as ".DB::getTablePrefix()."time_list"
                ), 'personal_orders.purchased_date', '=', 'time_list.times'
            )
            ->orderBy('time_list.times', 'asc')
            ->mergeBindings($subQuery);



            // past result

            $past_subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("DATE_FORMAT(".DB::getTablePrefix()."quin_order_item_meta.date_created, '%H:00:00') as purchased_date"),
                DB::raw('COALESCE(COUNT(DISTINCT '.DB::getTablePrefix().'wc_order_stats.order_id), 0) as accumulate_orders')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as '.DB::getTablePrefix().'personal_orders'))->select(
                DB::raw(DB::getTablePrefix().'time_list.times as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'personal_orders.accumulate_orders, 0) as accumulate_orders')
            )
            ->rightJoin(DB::raw("
                (select DATE_FORMAT(selected_date,'%H:%i:%s') as times
                from
                    (select addtime('1990-01-01 00:00:00', (t1.i*10 + t0.i)*10000) selected_date
                    from
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                        (select 0 i union select 1 union select 2) t1) as v
                    where selected_date between '1990-01-01 00:00:00' and '1990-01-01 23:59:59'
                    order by selected_date) as ".DB::getTablePrefix()."time_list"
                ), 'personal_orders.purchased_date', '=', 'time_list.times'
            )
            ->orderBy('time_list.times', 'asc')
            ->mergeBindings($past_subQuery);
        }
        else if((int) $request->o == 1 || (int) $request->o == 2 || (int) $request->o == 3)
        {
            // last 7 days or last month or last 3 months
            // day

            // current result
            $subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("CAST(".DB::getTablePrefix()."quin_order_item_meta.date_created AS DATE) as purchased_date"),
                DB::raw('COALESCE(COUNT(DISTINCT '.DB::getTablePrefix().'wc_order_stats.order_id), 0) as accumulate_orders')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $start_date)
            ->where('quin_order_item_meta.date_created', '<=', $end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');


            $result = DB::table(DB::raw('('.$subQuery->toSql().') as '.DB::getTablePrefix().'personal_orders'))->select(
                DB::raw(DB::getTablePrefix().'date_list.dates as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'personal_orders.accumulate_orders, 0) as accumulate_orders')
            )
            ->rightJoin(DB::raw("
                (select dates from
                (select adddate('2021-01-01', t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t2,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6) t3,
                        (select 0 i union select 1 union select 2 union select 3) t4) v
                where CAST(dates AS DATE) between '$start_date' and '$end_date') as ".DB::getTablePrefix()."date_list"
                ), 'personal_orders.purchased_date', '=', 'date_list.dates'
            )
            ->orderBy('date_list.dates', 'asc')
            ->mergeBindings($subQuery);



            // past result
            $past_subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("CAST(".DB::getTablePrefix()."quin_order_item_meta.date_created AS DATE) as purchased_date"),
                DB::raw('COALESCE(COUNT(DISTINCT '.DB::getTablePrefix().'wc_order_stats.order_id), 0) as accumulate_orders')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as '.DB::getTablePrefix().'personal_orders'))->select(
                DB::raw(DB::getTablePrefix().'date_list.dates as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'personal_orders.accumulate_orders, 0) as accumulate_orders')
            )
            ->rightJoin(DB::raw("
                (select dates from
                (select adddate('2021-01-01', t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t2,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6) t3,
                        (select 0 i union select 1 union select 2 union select 3) t4) v
                where CAST(dates AS DATE) between '$compared_to_start_date' and '$compared_to_end_date') as ".DB::getTablePrefix()."date_list"
                ), 'personal_orders.purchased_date', '=', 'date_list.dates'
            )
            ->orderBy('date_list.dates', 'asc')
            ->mergeBindings($past_subQuery);
        }
        else if ((int) $request->o == 4 || (int) $request->o == 5) {
            // last 6 months or last 12 months
            // month

            // current result
            $subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("DATE_FORMAT(".DB::getTablePrefix()."quin_order_item_meta.date_created, '%Y-%m') as purchased_date"),
                DB::raw('COALESCE(COUNT(DISTINCT '.DB::getTablePrefix().'wc_order_stats.order_id), 0) as accumulate_orders')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $start_date)
            ->where('quin_order_item_meta.date_created', '<=', $end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');


            $result = DB::table(DB::raw('('.$subQuery->toSql().') as '.DB::getTablePrefix().'personal_orders'))->select(
                DB::raw(DB::getTablePrefix().'month_list.months as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'personal_orders.accumulate_orders, 0) as accumulate_orders')
            )
            ->rightJoin(DB::raw("
                (select DATE_FORMAT(dates, '%Y-%m') as months
                from
                (select adddate('2019-09-01', INTERVAL (t1.i*10 + t0.i) MONTH) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                    (select 0 i union select 1 union select 2) t1) as v
                where DATE_FORMAT(dates, '%Y-%m-%d 00:00:00') between '$start_date' and '$end_date'
                order by dates) as ".DB::getTablePrefix()."month_list"
                ), 'personal_orders.purchased_date', '=', 'month_list.months'
            )
            ->orderBy('month_list.months', 'asc')
            ->mergeBindings($subQuery);



            // // past result
            $past_subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("DATE_FORMAT(".DB::getTablePrefix()."quin_order_item_meta.date_created, '%Y-%m') as purchased_date"),
                DB::raw('COALESCE(COUNT(DISTINCT '.DB::getTablePrefix().'wc_order_stats.order_id), 0) as accumulate_orders')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');


            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as '.DB::getTablePrefix().'personal_orders'))->select(
                DB::raw(DB::getTablePrefix().'month_list.months as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'personal_orders.accumulate_orders, 0) as accumulate_orders')
            )
            ->rightJoin(DB::raw("
                (select DATE_FORMAT(dates, '%Y-%m') as months
                from
                (select adddate('2019-09-01', INTERVAL (t1.i*10 + t0.i) MONTH) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                    (select 0 i union select 1 union select 2) t1) as v
                where DATE_FORMAT(dates, '%Y-%m-%d 00:00:00') between '$compared_to_start_date' and '$compared_to_end_date'
                order by dates) as ".DB::getTablePrefix()."month_list"
                ), 'personal_orders.purchased_date', '=', 'month_list.months'
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
                DB::raw('COALESCE(COUNT(DISTINCT '.DB::getTablePrefix().'wc_order_stats.order_id), 0) as accumulate_orders')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $start_date)
            ->where('quin_order_item_meta.date_created', '<=', $end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');


            $result = DB::table(DB::raw('('.$subQuery->toSql().') as '.DB::getTablePrefix().'personal_orders'))->select(
                DB::raw(DB::getTablePrefix().'date_list.dates as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'personal_orders.accumulate_orders, 0) as accumulate_orders')
            )
            ->rightJoin(DB::raw("
                (select dates from
                (select adddate('2021-01-01', t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t2,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6) t3,
                        (select 0 i union select 1 union select 2 union select 3) t4) v
                where CAST(dates AS DATE) between '$start_date' and '$end_date') as ".DB::getTablePrefix()."date_list"
                ), 'personal_orders.purchased_date', '=', 'date_list.dates'
            )
            ->orderBy('date_list.dates', 'asc')
            ->mergeBindings($subQuery);



            // past result
            $past_subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("CAST(".DB::getTablePrefix()."quin_order_item_meta.date_created AS DATE) as purchased_date"),
                DB::raw('COALESCE(COUNT(DISTINCT '.DB::getTablePrefix().'wc_order_stats.order_id), 0) as accumulate_orders')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->where('quin_users_meta.users_key', $key)
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as '.DB::getTablePrefix().'personal_orders'))->select(
                DB::raw(DB::getTablePrefix().'date_list.dates as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'personal_orders.accumulate_orders, 0) as accumulate_orders')
            )
            ->rightJoin(DB::raw("
                (select dates from
                (select adddate('2021-01-01', t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t2,
                        (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6) t3,
                        (select 0 i union select 1 union select 2 union select 3) t4) v
                where CAST(dates AS DATE) between '$compared_to_start_date' and '$compared_to_end_date') as ".DB::getTablePrefix()."date_list"
                ), 'personal_orders.purchased_date', '=', 'date_list.dates'
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
