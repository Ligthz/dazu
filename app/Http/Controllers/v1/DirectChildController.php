<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\QuinUser;

class DirectChildController extends Controller
{
     /**
     * Display the specified resource.
     *
     * @param  int  $key
     * @return \Illuminate\Http\Response
     */
    public function childrenShow($key)
    {
        try {
            $quin_user = QuinUser::where('users_key', $key)->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response([
                "error" => [
                    "code"=>"DCC001",
                    "message"=>"Resource not found!"
                ]
            ], 404);
        }

        $date = formatDateTimeZone(Carbon::now(), 1);

        $role = (int) $quin_user->getRole($date);

        $bonus_name = array();

        if($role == 1) {
            array_push($bonus_name, 'Same Level Bonus');
            array_push($bonus_name, 'Same Level Bonus');
            array_push($bonus_name, 'Same Level Bonus');
            array_push($bonus_name, 'Same Level Bonus');
        }
        else if($role == 2) {
            array_push($bonus_name, 'Distribution Bonus');
            array_push($bonus_name, 'Same Level Bonus');
            array_push($bonus_name, 'Same Level Bonus');
            array_push($bonus_name, 'Same Level Bonus');
        }
        else if($role == 3) {
            array_push($bonus_name, 'Distribution Bonus');
            array_push($bonus_name, 'Distribution Bonus');
            array_push($bonus_name, 'Same Level Bonus');
            array_push($bonus_name, 'Same Level Bonus');
        }
        else {
            array_push($bonus_name, 'Distribution Bonus');
            array_push($bonus_name, 'Distribution Bonus');
            array_push($bonus_name, 'Distribution Bonus');
            array_push($bonus_name, 'Refer to 3 Level Bonus');
        }


        $bonus_amount = array(0, 0, 0, 0);

        $bonus = DB::table("quin_roles_meta")
        ->select('direct_ba_bonus', 'direct_be_bonus', 'direct_bm_bonus', 'direct_bd_bonus')
        ->where('id', '=', $role)
        ->first();

        $bonus_amount[0] = (float) $bonus->direct_ba_bonus;
        $bonus_amount[1] = (float) $bonus->direct_be_bonus;
        $bonus_amount[2] = (float) $bonus->direct_bm_bonus;
        $bonus_amount[3] = (float) $bonus->direct_bd_bonus;



        $direct_name = array('TG', 'TE', 'TP', 'TM');
        $direct_count = array(0,0,0,0);
        $direct_sales = array(0,0,0,0);


        //get user current role
        $subquery = DB::table("quin_roles_history_meta")
        ->select('users_id', DB::raw('max(created_at) as role_date'))
        ->where('created_at', '<', $date)
        ->groupBy('users_id');

        $roles_data = DB::table('quin_roles_history_meta')
        ->select('quin_roles_history_meta.users_id', 'quin_roles_history_meta.roles', 'quin_roles_meta.short_name')
        ->join('quin_roles_meta', 'quin_roles_meta.id', '=', 'quin_roles_history_meta.roles')
        ->joinSub($subquery,'role_his', function ($join) {
            $join->on('quin_roles_history_meta.users_id', '=', 'role_his.users_id')
            ->on('quin_roles_history_meta.created_at', '=', 'role_his.role_date');
        })
        ->orderBy('quin_roles_history_meta.users_id');


        //calculate data
        $direct_children_amount = DB::table('quin_users_meta')
        ->select('roles_data.roles', 'roles_data.short_name', DB::raw('count(1) as num_of_children'))
        ->joinSub($roles_data, 'roles_data', function ($join) {
            $join->on('quin_users_meta.users_id', '=', 'roles_data.users_id');
        })
        ->whereIn('quin_users_meta.status', $quin_user->getAllowStatus())
        ->where('quin_users_meta.mentor_id','=', $quin_user->referral_code)
        ->where('roles_data.roles','>=', '1')
        ->where('roles_data.roles','<=', '4')
        ->groupBy('roles_data.roles')
        ->get();


        if($direct_children_amount != []){
            foreach ($direct_children_amount as $item) {
                $direct_count[((int) $item->roles - 1)] = (int) $item->num_of_children;
                $direct_name[((int) $item->roles - 1)] = $item->short_name;
            }
        }

        $date = formatDateTimeZone(Carbon::now(), 1);
        $start_date = date_format(date_create($date), "Y-m-01 00:00:00");
        $end_date = date_format(date_create($date), "Y-m-d H:i:s");

        $direct_sales_amount = DB::table('quin_order_item_meta')->select(
            'roles_data.roles',
            DB::raw('COALESCE(SUM(product_subtotal),0) as direct_sales')
        )
        ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
        ->join('quin_users_meta', function ($join) {
            $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id or ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
        })
        ->joinSub($roles_data, 'roles_data', function ($join) {
            $join->on('quin_users_meta.users_id', '=', 'roles_data.users_id');
        })
        ->whereIn('quin_users_meta.status', $quin_user->getAllowStatus())
        ->where('quin_users_meta.mentor_id','=', $quin_user->referral_code)
        ->where('roles_data.roles','>=', '1')
        ->where('roles_data.roles','<=', '4')
        ->where('quin_order_item_meta.date_created', '>=', $start_date)
        ->where('quin_order_item_meta.date_created', '<=', $end_date)
        ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
        ->groupBy('roles_data.roles')
        ->get();


        if($direct_sales_amount != []){
            foreach ($direct_sales_amount as $item) {
                $direct_sales[((int) $item->roles - 1)] = (float) $item->direct_sales;
            }
        }



        return response([
            'user_role' => $role,
            'bonus_name' => $bonus_name,
            'bonus_amount' => $bonus_amount,
            'children_name' => $direct_name,
            'children_count' => $direct_count,
            'children_sales' => $direct_sales
        ], 200);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $key)
    {

        $date = formatDateTimeZone(Carbon::now(), 1);

        if((int) $request->l == 0) {
            $start_level = 1;
            $end_level = 4;
        }
        else {
            $start_level = (int) $request->l;
            $end_level = (int) $request->l;
        }



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
        $query_order = 'children_name';
        $query_sort = 'asc';
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
        $result = DB::table('quin_users_meta')->select(
            'quin_users_meta.referral_code',
            'quin_users_meta.contact',
            DB::raw('CONCAT('.DB::getTablePrefix().'quin_users_meta.fname, " ", '.DB::getTablePrefix().'quin_users_meta.lname) as children_name'),
            DB::raw(DB::getTablePrefix().'quin_roles_meta.short_name as level'),
            DB::raw('COALESCE(ROUND(sum('.DB::getTablePrefix().'child_sales.product_subtotal), 2), 0) as children_sales')
        )
        ->distinct()
        ->join('users', 'quin_users_meta.users_id', '=', 'users.ID')
        ->join(DB::raw("
            (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id, ".DB::getTablePrefix()."quin_roles_history_meta.roles
            from ".DB::getTablePrefix()."quin_roles_history_meta
            inner join (
                select users_id, max(created_at) as role_date
                from ".DB::getTablePrefix()."quin_roles_history_meta
                group by users_id
            ) as latest
            on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
            join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
            where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
            ), 'quin_users_meta.users_id', '=', 'children.users_id'
        )
        ->join('quin_roles_meta', 'quin_roles_meta.id', '=', 'children.roles')
        ->leftJoin(DB::raw("
                (select ".DB::getTablePrefix()."quin_order_item_meta.sold_by, ".DB::getTablePrefix()."quin_order_item_meta.product_subtotal, ".DB::getTablePrefix()."quin_order_item_meta.customer_id, ".DB::getTablePrefix()."quin_order_item_meta.date_created
                from ".DB::getTablePrefix()."quin_order_item_meta
                join ".DB::getTablePrefix()."wc_order_stats on ".DB::getTablePrefix()."quin_order_item_meta.order_id = ".DB::getTablePrefix()."wc_order_stats.order_id
                where ".DB::getTablePrefix()."quin_order_item_meta.date_created >= '$start_date'
                    and ".DB::getTablePrefix()."quin_order_item_meta.date_created <= '$end_date'
                    and (".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')
                ) as ".DB::getTablePrefix()."child_sales"
            ),
            function($join) {
                $join->on('child_sales.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_users_meta.referral_code = ".DB::getTablePrefix()."child_sales.sold_by OR ".DB::getTablePrefix()."quin_users_meta.users_id = ".DB::getTablePrefix()."child_sales.customer_id)");
            }
        )
        ->groupBy('quin_users_meta.referral_code', 'quin_users_meta.contact', 'children_name', 'quin_roles_meta.short_name')
        ->having('quin_users_meta.referral_code', 'LIKE', $query_search)
        ->orHaving('quin_users_meta.contact', 'LIKE', $query_search)
        ->orHaving('children_name', 'LIKE', $query_search)
        ->orHaving('level', 'LIKE', $query_search)
        ->orHaving('children_sales', 'LIKE', $query_search)
        ->orderBy($query_order, $query_sort)
        ->paginate($query_paginate);

        return response([
            "records" => $result
        ], 200);
    }


    /**
     * Display the specified resource.
     * @param  int  $key
     * @return \Illuminate\Http\Response
     */
    public function salesChart(Request $request, $key)
    {

        $date = formatDateTimeZone(Carbon::now(), 1);

        if((int) $request->l == 0) {
            $start_level = 1;
            $end_level = 4;
        }
        else {
            $start_level = (int) $request->l;
            $end_level = (int) $request->l;
        }



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
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->join(DB::raw("
                (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
                from ".DB::getTablePrefix()."quin_roles_history_meta
                inner join (
                    select users_id, max(created_at) as role_date
                    from ".DB::getTablePrefix()."quin_roles_history_meta
                    group by users_id
                ) as latest
                on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                    and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
                join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
                where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                    and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                    and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
                ), 'quin_users_meta.users_id', '=', 'children.users_id'
            )
            ->where('quin_order_item_meta.date_created', '>=', $start_date)
            ->where('quin_order_item_meta.date_created', '<=', $end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $result = DB::table(DB::raw('('.$subQuery->toSql().') as '.DB::getTablePrefix().'children_sales'))->select(
                DB::raw(DB::getTablePrefix().'time_list.times as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'children_sales.accumulate_sales, 0) as level_sales')
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
                ), 'children_sales.purchased_date', '=', 'time_list.times'
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
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->join(DB::raw("
                (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
                from ".DB::getTablePrefix()."quin_roles_history_meta
                inner join (
                    select users_id, max(created_at) as role_date
                    from ".DB::getTablePrefix()."quin_roles_history_meta
                    group by users_id
                ) as latest
                on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                    and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
                join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
                where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                    and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                    and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
                ), 'quin_users_meta.users_id', '=', 'children.users_id'
            )
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');


            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as '.DB::getTablePrefix().'children_sales'))->select(
                DB::raw(DB::getTablePrefix().'time_list.times as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'children_sales.accumulate_sales, 0) as level_sales')
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
                ), 'children_sales.purchased_date', '=', 'time_list.times'
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
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->join(DB::raw("
                (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
                from ".DB::getTablePrefix()."quin_roles_history_meta
                inner join (
                    select users_id, max(created_at) as role_date
                    from ".DB::getTablePrefix()."quin_roles_history_meta
                    group by users_id
                ) as latest
                on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                    and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
                join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
                where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                    and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                    and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
                ), 'quin_users_meta.users_id', '=', 'children.users_id'
            )
            ->where('quin_order_item_meta.date_created', '>=', $start_date)
            ->where('quin_order_item_meta.date_created', '<=', $end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $result = DB::table(DB::raw('('.$subQuery->toSql().') as '.DB::getTablePrefix().'children_sales'))->select(
                DB::raw(DB::getTablePrefix().'date_list.dates as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'children_sales.accumulate_sales, 0) as level_sales')
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
                ), 'children_sales.purchased_date', '=', 'date_list.dates'
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
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->join(DB::raw("
                (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
                from ".DB::getTablePrefix()."quin_roles_history_meta
                inner join (
                    select users_id, max(created_at) as role_date
                    from ".DB::getTablePrefix()."quin_roles_history_meta
                    group by users_id
                ) as latest
                on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                    and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
                join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
                where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                    and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                    and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
                ), 'quin_users_meta.users_id', '=', 'children.users_id'
            )
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as '.DB::getTablePrefix().'children_sales'))->select(
                DB::raw(DB::getTablePrefix().'date_list.dates as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'children_sales.accumulate_sales, 0) as level_sales')
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
                ), 'children_sales.purchased_date', '=', 'date_list.dates'
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
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->join(DB::raw("
                (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
                from ".DB::getTablePrefix()."quin_roles_history_meta
                inner join (
                    select users_id, max(created_at) as role_date
                    from ".DB::getTablePrefix()."quin_roles_history_meta
                    group by users_id
                ) as latest
                on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                    and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
                join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
                where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                    and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                    and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
                ), 'quin_users_meta.users_id', '=', 'children.users_id'
            )
            ->where('quin_order_item_meta.date_created', '>=', $start_date)
            ->where('quin_order_item_meta.date_created', '<=', $end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $result = DB::table(DB::raw('('.$subQuery->toSql().') as '.DB::getTablePrefix().'children_sales'))->select(
                DB::raw(DB::getTablePrefix().'month_list.months as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'children_sales.accumulate_sales, 0) as level_sales')
            )
            ->rightJoin(DB::raw("
                (select DATE_FORMAT(dates, '%Y-%m') as months
                from
                (select adddate('2019-09-01', INTERVAL (t1.i*10 + t0.i) MONTH) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                    (select 0 i union select 1 union select 2) t1) as v
                where DATE_FORMAT(dates, '%Y-%m-%d 00:00:00') between '$start_date' and '$end_date'
                order by dates) as ".DB::getTablePrefix()."month_list"
                ), 'children_sales.purchased_date', '=', 'month_list.months'
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
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->join(DB::raw("
                (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
                from ".DB::getTablePrefix()."quin_roles_history_meta
                inner join (
                    select users_id, max(created_at) as role_date
                    from ".DB::getTablePrefix()."quin_roles_history_meta
                    group by users_id
                ) as latest
                on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                    and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
                join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
                where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                    and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                    and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
                ), 'quin_users_meta.users_id', '=', 'children.users_id'
            )
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as '.DB::getTablePrefix().'children_sales'))->select(
                DB::raw(DB::getTablePrefix().'month_list.months as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'children_sales.accumulate_sales, 0) as level_sales')
            )
            ->rightJoin(DB::raw("
                (select DATE_FORMAT(dates, '%Y-%m') as months
                from
                (select adddate('2019-09-01', INTERVAL (t1.i*10 + t0.i) MONTH) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                    (select 0 i union select 1 union select 2) t1) as v
                where DATE_FORMAT(dates, '%Y-%m-%d 00:00:00') between '$compared_to_start_date' and '$compared_to_end_date'
                order by dates) as ".DB::getTablePrefix()."month_list"
                ), 'children_sales.purchased_date', '=', 'month_list.months'
            )
            ->orderBy('month_list.months', 'asc')
            ->mergeBindings($past_subQuery);
        }
        else {
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
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->join(DB::raw("
                (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
                from ".DB::getTablePrefix()."quin_roles_history_meta
                inner join (
                    select users_id, max(created_at) as role_date
                    from ".DB::getTablePrefix()."quin_roles_history_meta
                    group by users_id
                ) as latest
                on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                    and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
                join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
                where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                    and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                    and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
                ), 'quin_users_meta.users_id', '=', 'children.users_id'
            )
            ->where('quin_order_item_meta.date_created', '>=', $start_date)
            ->where('quin_order_item_meta.date_created', '<=', $end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $result = DB::table(DB::raw('('.$subQuery->toSql().') as '.DB::getTablePrefix().'children_sales'))->select(
                DB::raw(DB::getTablePrefix().'time_list.times as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'children_sales.accumulate_sales, 0) as level_sales')
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
                ), 'children_sales.purchased_date', '=', 'time_list.times'
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
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->join(DB::raw("
                (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
                from ".DB::getTablePrefix()."quin_roles_history_meta
                inner join (
                    select users_id, max(created_at) as role_date
                    from ".DB::getTablePrefix()."quin_roles_history_meta
                    group by users_id
                ) as latest
                on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                    and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
                join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
                where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                    and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                    and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
                ), 'quin_users_meta.users_id', '=', 'children.users_id'
            )
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');


            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as '.DB::getTablePrefix().'children_sales'))->select(
                DB::raw(DB::getTablePrefix().'time_list.times as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'children_sales.accumulate_sales, 0) as level_sales')
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
                ), 'children_sales.purchased_date', '=', 'time_list.times'
            )
            ->orderBy('time_list.times', 'asc')
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
     * Display the specified resource.
     * @param  int  $key
     * @return \Illuminate\Http\Response
     */
    public function ordersChart(Request $request, $key)
    {

        $date = formatDateTimeZone(Carbon::now(), 1);

        if((int) $request->l == 0) {
            $start_level = 1;
            $end_level = 4;
        }
        else {
            $start_level = (int) $request->l;
            $end_level = (int) $request->l;
        }



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
         *  Orders - Chart
        */
        if((int) $request->o == 0) {
            // today
            // time

            // current result
            $subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("DATE_FORMAT(".DB::getTablePrefix()."quin_order_item_meta.date_created, '%H:00:00') as purchased_date"),
                DB::raw('COALESCE(COUNT(DISTINCT wc_order_stats.order_id), 0) as accumulate_orders')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->join(DB::raw("
                (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
                from ".DB::getTablePrefix()."quin_roles_history_meta
                inner join (
                    select users_id, max(created_at) as role_date
                    from ".DB::getTablePrefix()."quin_roles_history_meta
                    group by users_id
                ) as latest
                on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                    and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
                join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
                where ".DB::getTablePrefix()."uin_roles_history_meta.roles >= $start_level
                    and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                    and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
                ), 'quin_users_meta.users_id', '=', 'children.users_id'
            )
            ->where('quin_order_item_meta.date_created', '>=', $start_date)
            ->where('quin_order_item_meta.date_created', '<=', $end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');


            $result = DB::table(DB::raw('('.$subQuery->toSql().') as '.DB::getTablePrefix().'children_sales'))->select(
                DB::raw(DB::getTablePrefix().'time_list.times as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'children_sales.accumulate_orders, 0) as level_orders')
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
                ), 'children_sales.purchased_date', '=', 'time_list.times'
            )
            ->orderBy('time_list.times', 'asc')
            ->mergeBindings($subQuery);



            // past result
            $past_subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("DATE_FORMAT(".DB::getTablePrefix()."quin_order_item_meta.date_created, '%H:00:00') as purchased_date"),
                DB::raw('COALESCE(COUNT(DISTINCT wc_order_stats.order_id), 0) as accumulate_orders')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->join(DB::raw("
                (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
                from ".DB::getTablePrefix()."quin_roles_history_meta
                inner join (
                    select users_id, max(created_at) as role_date
                    from ".DB::getTablePrefix()."quin_roles_history_meta
                    group by users_id
                ) as latest
                on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                    and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
                join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
                where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                    and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                    and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
                ), 'quin_users_meta.users_id', '=', 'children.users_id'
            )
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as '.DB::getTablePrefix().'children_sales'))->select(
                DB::raw(DB::getTablePrefix().'time_list.times as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'children_sales.accumulate_orders, 0) as level_orders')
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
                ), 'children_sales.purchased_date', '=', 'time_list.times'
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
                DB::raw('COALESCE(COUNT(DISTINCT wc_order_stats.order_id), 0) as accumulate_orders')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->join(DB::raw("
                (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
                from ".DB::getTablePrefix()."quin_roles_history_meta
                inner join (
                    select users_id, max(created_at) as role_date
                    from ".DB::getTablePrefix()."quin_roles_history_meta
                    group by users_id
                ) as latest
                on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                    and quin_roles_history_meta.created_at = latest.role_date
                join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
                where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                    and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                    and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
                ), 'quin_users_meta.users_id', '=', 'children.users_id'
            )
            ->where('quin_order_item_meta.date_created', '>=', $start_date)
            ->where('quin_order_item_meta.date_created', '<=', $end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $result = DB::table(DB::raw('('.$subQuery->toSql().') as '.DB::getTablePrefix().'children_sales'))->select(
                DB::raw(DB::getTablePrefix().'date_list.dates as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'children_sales.accumulate_orders, 0) as level_orders')
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
                ), 'children_sales.purchased_date', '=', 'date_list.dates'
            )
            ->orderBy('date_list.dates', 'asc')
            ->mergeBindings($subQuery);



            // past result
            $past_subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("CAST(".DB::getTablePrefix()."quin_order_item_meta.date_created AS DATE) as purchased_date"),
                DB::raw('COALESCE(COUNT(DISTINCT wc_order_stats.order_id), 0) as accumulate_orders')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->join(DB::raw("
                (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
                from ".DB::getTablePrefix()."quin_roles_history_meta
                inner join (
                    select users_id, max(created_at) as role_date
                    from ".DB::getTablePrefix()."quin_roles_history_meta
                    group by users_id
                ) as latest
                on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                    and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
                join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
                where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                    and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                    and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
                ), 'quin_users_meta.users_id', '=', 'children.users_id'
            )
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as '.DB::getTablePrefix().'children_sales'))->select(
                DB::raw(DB::getTablePrefix().'date_list.dates as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'children_sales.accumulate_orders, 0) as level_orders')
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
                ), 'children_sales.purchased_date', '=', 'date_list.dates'
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
                DB::raw('COALESCE(COUNT(DISTINCT wc_order_stats.order_id), 0) as accumulate_orders')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->join(DB::raw("
                (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
                from ".DB::getTablePrefix()."quin_roles_history_meta
                inner join (
                    select users_id, max(created_at) as role_date
                    from ".DB::getTablePrefix()."quin_roles_history_meta
                    group by users_id
                ) as latest
                on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                    and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
                join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
                where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                    and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                    and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
                ), 'quin_users_meta.users_id', '=', 'children.users_id'
            )
            ->where('quin_order_item_meta.date_created', '>=', $start_date)
            ->where('quin_order_item_meta.date_created', '<=', $end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $result = DB::table(DB::raw('('.$subQuery->toSql().') as '.DB::getTablePrefix().'children_sales'))->select(
                DB::raw(DB::getTablePrefix().'month_list.months as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'children_sales.accumulate_orders, 0) as level_orders')
            )
            ->rightJoin(DB::raw("
                (select DATE_FORMAT(dates, '%Y-%m') as months
                from
                (select adddate('2019-09-01', INTERVAL (t1.i*10 + t0.i) MONTH) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                    (select 0 i union select 1 union select 2) t1) as v
                where DATE_FORMAT(dates, '%Y-%m-%d 00:00:00') between '$start_date' and '$end_date'
                order by dates) as ".DB::getTablePrefix()."month_list"
                ), 'children_sales.purchased_date', '=', 'month_list.months'
            )
            ->orderBy('month_list.months', 'asc')
            ->mergeBindings($subQuery);



            // // past result
            $past_subQuery = DB::table('quin_order_item_meta')->select(
                DB::raw("DATE_FORMAT(".DB::getTablePrefix()."quin_order_item_meta.date_created, '%Y-%m') as purchased_date"),
                DB::raw('COALESCE(COUNT(DISTINCT wc_order_stats.order_id), 0) as accumulate_orders')
            )
            ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
            ->join('quin_users_meta', function ($join) {
                $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->join(DB::raw("
                (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
                from ".DB::getTablePrefix()."quin_roles_history_meta
                inner join (
                    select users_id, max(created_at) as role_date
                    from ".DB::getTablePrefix()."quin_roles_history_meta
                    group by users_id
                ) as latest
                on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                    and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
                join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
                where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                    and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                    and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
                ), 'quin_users_meta.users_id', '=', 'children.users_id'
            )
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as '.DB::getTablePrefix().'children_sales'))->select(
                DB::raw(DB::getTablePrefix().'month_list.months as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'children_sales.accumulate_orders, 0) as level_orders')
            )
            ->rightJoin(DB::raw("
                (select DATE_FORMAT(dates, '%Y-%m') as months
                from
                (select adddate('2019-09-01', INTERVAL (t1.i*10 + t0.i) MONTH) dates
                from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                    (select 0 i union select 1 union select 2) t1) as v
                where DATE_FORMAT(dates, '%Y-%m-%d 00:00:00') between '$compared_to_start_date' and '$compared_to_end_date'
                order by dates) as ".DB::getTablePrefix()."month_list"
                ), 'children_sales.purchased_date', '=', 'month_list.months'
            )
            ->orderBy('month_list.months', 'asc')
            ->mergeBindings($past_subQuery);
        }
        else {
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
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->join(DB::raw("
                (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
                from ".DB::getTablePrefix()."quin_roles_history_meta
                inner join (
                    select users_id, max(created_at) as role_date
                    from ".DB::getTablePrefix()."quin_roles_history_meta
                    group by users_id
                ) as latest
                on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                    and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
                join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
                where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                    and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                    and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
                ), 'quin_users_meta.users_id', '=', 'children.users_id'
            )
            ->where('quin_order_item_meta.date_created', '>=', $start_date)
            ->where('quin_order_item_meta.date_created', '<=', $end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');


            $result = DB::table(DB::raw('('.$subQuery->toSql().') as '.DB::getTablePrefix().'children_sales'))->select(
                DB::raw(DB::getTablePrefix().'time_list.times as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'children_sales.accumulate_orders, 0) as level_orders')
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
                ), 'children_sales.purchased_date', '=', 'time_list.times'
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
                    ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
            })
            ->join(DB::raw("
                (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
                from ".DB::getTablePrefix()."quin_roles_history_meta
                inner join (
                    select users_id, max(created_at) as role_date
                    from ".DB::getTablePrefix()."quin_roles_history_meta
                    group by users_id
                ) as latest
                on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                    and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
                join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
                where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                    and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                    and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
                ), 'quin_users_meta.users_id', '=', 'children.users_id'
            )
            ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
            ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
            ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
            ->groupBy('purchased_date');

            $past_result = DB::table(DB::raw('('.$past_subQuery->toSql().') as '.DB::getTablePrefix().'children_sales'))->select(
                DB::raw(DB::getTablePrefix().'time_list.times as dates'),
                DB::raw('COALESCE('.DB::getTablePrefix().'children_sales.accumulate_orders, 0) as level_orders')
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
                ), 'children_sales.purchased_date', '=', 'time_list.times'
            )
            ->orderBy('time_list.times', 'asc')
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
     *
     * @param  int  $key
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $key)
    {

        $date = formatDateTimeZone(Carbon::now(), 1);

        if((int) $request->l == 0) {
            $start_level = 1;
            $end_level = 4;
        }
        else {
            $start_level = (int) $request->l;
            $end_level = (int) $request->l;
        }



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




        /* Group Sales - Number */
        // using referral code and self purchase

        // current period
        $group_sales = DB::table('quin_order_item_meta')->select(
            DB::raw('COALESCE(ROUND(SUM('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 2), 0) as group_sales')
        )
        ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
        ->join('quin_users_meta', function ($join) {
            $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
        })
        ->join(DB::raw("
            (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
            from ".DB::getTablePrefix()."quin_roles_history_meta
            inner join (
                select users_id, max(created_at) as role_date
                from ".DB::getTablePrefix()."quin_roles_history_meta
                group by users_id
            ) as latest
            on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
            join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
            where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
            ), 'quin_users_meta.users_id', '=', 'children.users_id'
        )
        ->where('quin_order_item_meta.date_created', '>=', $start_date)
        ->where('quin_order_item_meta.date_created', '<=', $end_date)
        ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
        ->get();


        // compared to last period
        $past_sales = DB::table('quin_order_item_meta')->select(
            DB::raw('COALESCE(ROUND(SUM('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 2), 0) as past_sales')
        )
        ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
        ->join('quin_users_meta', function ($join) {
            $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
        })
        ->join(DB::raw("
            (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
            from ".DB::getTablePrefix()."quin_roles_history_meta
            inner join (
                select users_id, max(created_at) as role_date
                from ".DB::getTablePrefix()."quin_roles_history_meta
                group by users_id
            ) as latest
            on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
            join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
            where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
            ), 'quin_users_meta.users_id', '=', 'children.users_id'
        )
        ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
        ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
        ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
        ->get();


        $group_sales = (float) $group_sales[0]->group_sales;
        $past_sales = (float) $past_sales[0]->past_sales;

        // calculate sales percentage
        if ($past_sales <= 0 && $group_sales <= 0) {
            $sales_percent = 0;
        }
        else if ($past_sales <= 0) {
            $sales_percent = 100;
        }
        else {
            $sales_percent = ($group_sales - $past_sales) / $past_sales * 100;
        }

        // determine is sales percentage increment or decrement
        if($sales_percent >= 0) {
            $sales_stat = 1;
        }
        else {
            $sales_stat = 2;
        }



        /* Group Orders - Number */
        $group_orders = DB::table('quin_order_item_meta')->select(
            DB::raw('COALESCE(COUNT(DISTINCT '.DB::getTablePrefix().'quin_order_item_meta.order_id), 0) as group_orders')
        )
        ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
        ->join('quin_users_meta', function ($join) {
            $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
        })
        ->join(DB::raw("
            (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
            from ".DB::getTablePrefix()."quin_roles_history_meta
            inner join (
                select users_id, max(created_at) as role_date
                from ".DB::getTablePrefix()."quin_roles_history_meta
                group by users_id
            ) as latest
            on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
            join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
            where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
            ), 'quin_users_meta.users_id', '=', 'children.users_id'
        )
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
                ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
        })
        ->join(DB::raw("
            (select ".DB::getTablePrefix()."quin_roles_history_meta.users_id
            from ".DB::getTablePrefix()."quin_roles_history_meta
            inner join (
                select users_id, max(created_at) as role_date
                from ".DB::getTablePrefix()."quin_roles_history_meta
                group by users_id
            ) as latest
            on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = latest.users_id
                and ".DB::getTablePrefix()."quin_roles_history_meta.created_at = latest.role_date
            join ".DB::getTablePrefix()."quin_users_meta on ".DB::getTablePrefix()."quin_roles_history_meta.users_id = ".DB::getTablePrefix()."quin_users_meta.users_id
            where ".DB::getTablePrefix()."quin_roles_history_meta.roles >= $start_level
                and ".DB::getTablePrefix()."quin_roles_history_meta.roles <= $end_level
                and ".DB::getTablePrefix()."quin_users_meta.mentor_id = (select referral_code from ".DB::getTablePrefix()."quin_users_meta where users_key = '$key')) as ".DB::getTablePrefix()."children"
            ), 'quin_users_meta.users_id', '=', 'children.users_id'
        )
        ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
        ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
        ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
        ->get();


        $group_orders = (int) $group_orders[0]->group_orders;
        $past_orders = (int) $past_orders[0]->past_orders;

        // calculate sales percentage
        if ($past_orders <= 0 && $group_orders <= 0) {
            $orders_percent = 0;
        }
        else if ($past_orders <= 0) {
            $orders_percent = 100;
        }
        else {
            $orders_percent = ($group_orders - $past_orders) / $past_orders * 100;
        }

        // determine is sales percentage increment or decrement
        if($orders_percent >= 0) {
            $orders_stat = 1;
        }
        else {
            $orders_stat = 2;
        }


        return response([
            'group_sales' => $group_sales,
            'past_sales' => $past_sales,
            'sales_percentage' => abs(round($sales_percent, 0)) . '%',
            'sales_status' => $sales_stat,
            'group_orders' => $group_orders,
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
}
