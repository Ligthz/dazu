<?php

namespace App\Http\Controllers\v1;


use App\Jobs\ImportCsv;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Throwable;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\QuinUser;
use App\Models\QuinRolesHistory;
use App\Http\Resources\QuinUser as QuinUserResource;
use App\Jobs\CalculateBDSales;
use App\Jobs\CaptureBDSales;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Jobs\CaptureDailySales;
use App\Jobs\CaptureDirectChildrenSales;
use App\Jobs\CaptureGroupSales;
use App\Models\DailySales;


use App\Jobs\CaptureDailyCommissions;
use App\Jobs\CaptureDirectChildrenCommissions;
use App\Jobs\CaptureGroupCommissions;
use App\Jobs\CaptureBDCommissions;
use App\Jobs\CaptureLevelUpgrade;

use App\Jobs\CaptureMonthlyCommissions;
use App\Jobs\CaptureMonthlyPayout;



use App\Jobs\CalculateIndividualDailySales;
use App\Jobs\CalculateDirectChildren;
use App\Jobs\CalculateGroupChildren;
use App\Jobs\CalculateFirstBDSales;
use App\Jobs\CalculateSecondBDSales;

use DateInterval;
use DatePeriod;
use DateTime;

class CommissionController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function personalCommission(Request $request, $key)
    {
        $rules = [
            'startDate' => 'required',
            'endDate' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            //TODO Handle your error
            return response([
                "error" => [
                    "code" => "PCC001",
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
                    "code" => "PCC002",
                    "message" => "Invalid date input!"
                ]
            ], 400);
        }

        if((int) $diff->format("%R%a") < 0) {
            // Invalid date range
            return response([
                "error" => [
                    "code" => "PCC003",
                    "message" => "Invalid date range!"
                ]
            ], 400);
        }


        /* Personal Sales - Number */

        // current period
        $referral_sales = DB::table('quin_order_item_meta')->select(
            DB::raw('COALESCE(ROUND(SUM('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 2), 0) as current_sales')
        )
        ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
        ->join('quin_users_meta', function ($join) {
            $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                ->on('quin_order_item_meta.sold_by', '=', 'quin_users_meta.referral_code');
        })
        ->where('quin_users_meta.users_key', $key)
        ->where('quin_order_item_meta.date_created', '>=', $request->startDate)
        ->where('quin_order_item_meta.date_created', '<=', $request->endDate)
        ->where('wc_order_stats.status', '=', 'wc-completed')
        ->get();

        // $personal_sales = DB::table('quin_order_item_meta')->select(
        //     DB::raw('COALESCE(ROUND(SUM('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 2), 0) as current_sales')
        // )
        // ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
        // ->join('quin_users_meta', function ($join) {
        //     $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
        //         ->on('quin_order_item_meta.customer_id', '=', 'quin_users_meta.users_id');
        // })
        // ->where('quin_users_meta.users_key', $key)
        // ->where('quin_order_item_meta.date_created', '>=', $request->startDate)
        // ->where('quin_order_item_meta.date_created', '<=', $request->endDate)
        // ->where('wc_order_stats.status', '=', 'wc-completed')
        // ->get();

        $personal_sales = (float) $referral_sales[0]->current_sales;

        return response([
            "personal" => $personal_sales
        ], 200);

    }


    public function groupCommissionCalculation()
    {
        $var = CalculateDailySales::dispatch('2021-09-25 00:00:00');
        return response([
            "status" => json_encode($var)
        ], 200);

        // result
        // $result = DB::table('quin_users_meta')->select(
        //     'quin_users_meta.referral_code',
        //     DB::raw('COALESCE(ROUND(sum(child_sales.product_subtotal), 2), 0) as personal_sales')
        // )
        // ->distinct()
        // ->join('users', 'quin_users_meta.users_id', '=', 'users.ID')
        // ->join(DB::raw("
        //     (select quin_roles_history_meta.users_id, quin_roles_history_meta.roles
        //     from quin_roles_history_meta
        //     inner join (
        //         select users_id, max(created_at) as role_date
        //         from quin_roles_history_meta
        //         group by users_id
        //     ) as latest
        //     on quin_roles_history_meta.users_id = latest.users_id
        //         and quin_roles_history_meta.created_at = latest.role_date
        //     join quin_users_meta on quin_roles_history_meta.users_id = quin_users_meta.users_id
        //     where quin_roles_history_meta.roles >= 1
        //         and quin_roles_history_meta.roles <= 3
        //         and quin_users_meta.mentor_id = '$code') as children"
        //     ), 'quin_users_meta.users_id', '=', 'children.users_id'
        // )
        // ->leftJoin(DB::raw("
        //         (select quin_order_item_meta.sold_by, quin_order_item_meta.product_subtotal, quin_order_item_meta.customer_id, quin_order_item_meta.date_created
        //         from quin_order_item_meta
        //         join wc_order_stats on quin_order_item_meta.order_id = wc_order_stats.order_id
        //         where quin_order_item_meta.date_created >= '$request->startDate'
        //             and quin_order_item_meta.date_created <= '$request->endDate'
        //             and (".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or wc_order_stats.status = 'wc-shipping' or wc_order_stats.status = 'wc-completed')
        //         ) as child_sales"
        //     ),
        //     function($join) {
        //         $join->on('child_sales.date_created', '>', 'quin_users_meta.partner_joined_at')
        //             ->whereRaw("(".DB::getTablePrefix()."quin_users_meta.referral_code = child_sales.sold_by OR quin_users_meta.users_id = child_sales.customer_id)");
        //     }
        // )
        // ->groupBy('quin_users_meta.referral_code')
        // ->orderBy('personal_sales', 'desc')
        // ->get();

        // $total = '';

        // foreach($result as $child) {
        //     // $total += $child->personal_sales;
        //     // $total += $this->groupCommissionCalculation($request, $child->referral_code, true);

        //     $total .= $child->referral_code . '<br>';
        //     $total .= $this->groupCommissionCalculation($request, $child->referral_code, true);
        // }

        // if ($isRecursive) {
        //     return $total;
        // }
        // else {
        //     return response([
        //         "total" => $total
        //     ], 200);
        // }



        /*groupSales(tree, exceptBD = true, self = true){
            let total = 0;






            if(tree.children.length > 0){
                tree.children.forEach(element=>{
                    if(exceptBD === false || element.position != "Big Director (BD)"){
                        total += parseInt(this.groupSales(element, exceptBD));
                    }
                })
            }

            if(self === true){
                total += parseInt(tree.sales);
            }
            return total;
        },*/
    }

    public function recuive($code){

        $children = QuinUser::where('mentor_id',$code)->get();
        $i = 0;
        while ($i < $children->count())
        {
            $parent = QuinUser::where('mentor_id',$children[$i]->referral_code)->get();
            $children = $children->merge($parent);
            $i++;
        }
        return $children;

    }

    public function test($ref_code){
        //$ref_code = 'BW213060';
        $allow_status = [21,23];
        // $start_date = formatDateTimeZone(Carbon::now(), 1);

        // return $group_coms = (object) array(
        //     'group_bonus' => null,
        //     'group_commissions' => null
        // );

        $start_date = '2021-12-20';
        // $temp_user = QuinUser::where('referral_code', $ref_code)->first();
        // return $temp_user->recordPersonalCommissions($start_date);

        // $date = formatDateTimeZone(Carbon::now(), 1);

        // $start_date = date_create($date);
        // date_sub($start_date, date_interval_create_from_date_string("1 months"));
        // $start_date = date_format($start_date, "Y-m-01 00:00:00");

        // $end_date = date_format(date_create($date), "Y-m-01 00:00:00");


        // $first_of_month = new DateTime($start_date);
        // $end_of_month = new DateTime($end_date);

        // $interval = DateInterval::createFromDateString('1 day');
        // $period = new DatePeriod($first_of_month, $interval, $end_of_month);

        // foreach ($period as $dt) {
        //     echo $dt->format("Y-m-d H:i:s");

        // }

        // return 0;
        // return $temp_user->recordLvlUpgrade($start_date);



        // $start_date = '2021-12-08 00:00:00';
        // return $temp_user->recordGroupSales($start_date);

        /////////////////////
        // $validStatus = [21, 23];
        // $quinUsers = QuinUser::where([
        //     ['users_id', '!=', 1],
        //     ['users_id', '!=', 2]
        // ])->whereIn('status', $validStatus)->get();

        // return $quinUsers;

        // $bus_chain = Bus::chain([
        //     new CaptureDailySales($start_date)
        //     // new CaptureDirectChildrenSales($start_date),
        //     // new CaptureGroupSales($start_date),
        //     // new CaptureBDSales($start_date),
        //     // new CaptureLevelUpgrade($start_date)
        // ])->catch(function (Throwable $e) {
        //     $result = DB::table('quin_error_log')->insert([
        //         'msg' => $e
        //     ]);
        // })->dispatch();

        // return $bus_chain;

        // $start_date = '2021-12-01';
        // $end_date = '2021-12-31';

        // $temp_user = QuinUser::where('referral_code', $ref_code)->first();

        // return $temp_user->recordMonthlyPayout($start_date, $end_date);



        // $date = formatDateTimeZone(date("Y-m-d 00:00:00", strtotime("-1 day", strtotime(Carbon::now()))), 1);
        // $start_date = '2021-12-01';
        // $end_date = '2021-12-31';

        // $bus_chain = Bus::chain([
        //     new CaptureMonthlyCommissions($date),
        //     new CaptureDirectChildrenCommissions($date),
        //     new CaptureGroupCommissions($date),
        //     new CaptureBDCommissions($date),
        //     new CaptureMonthlyPayout($start_date, $end_date)

        // ])->catch(function (Throwable $e) {
        //     $result = DB::table('quin_error_log')->insert([
        //         'msg' => $e
        //     ]);
        // })->dispatch();

        // return $bus_chain;

        // $individual_arr = array();
        // $direct_arr = array();
        // $group_arr = array();
        // $first_bd_arr = array();
        // $second_bd_arr = array();

        // $validStatus = [21, 23];
        // $quinUsers = QuinUser::whereIn('status', $validStatus)->get();

        // foreach($quinUsers as $user) {
        //     array_push($individual_arr, new CalculateIndividualDailySales($user, $start_date));
        //     array_push($direct_arr, new CalculateDirectChildren($user, $start_date));
        // }


        // $subquery = DB::table("quin_roles_history_meta")
        // ->select('users_id', DB::raw('max(created_at) as role_date'))
        // ->where('created_at', '<', $start_date)
        // ->groupBy('users_id');

        // $all_bd = DB::table('quin_roles_history_meta')
        // ->select('quin_roles_history_meta.users_id', 'quin_roles_history_meta.roles')
        // ->joinSub($subquery,'role_his', function ($join) {
        //     $join->on('quin_roles_history_meta.users_id', '=', 'role_his.users_id')
        //     ->on('quin_roles_history_meta.created_at', '=', 'role_his.role_date');
        // })->where('quin_roles_history_meta.roles','=',4)
        // ->get();

        // foreach($all_bd as $bd_id) {
        //     $user = QuinUser::where('users_id', $bd_id->users_id)->whereIn('status', $validStatus)->first();
        //     array_push($group_arr, new CalculateGroupChildren($user, $start_date));
        //     array_push($first_bd_arr, new CalculateFirstBDSales($user, $start_date));
        //     array_push($second_bd_arr, new CalculateSecondBDSales($user, $start_date));
        // }

        // $batch1 = Bus::batch($individual_arr)->then(function (Batch $batch1) {
        //     $result = DB::table('quin_error_log')->insert([
        //         'msg' => 'then'
        //     ]);
        //     // All jobs completed successfully...
        // })->catch(function (Batch $batch1, Throwable $e) {
        //     $result = DB::table('quin_error_log')->insert([
        //         'msg' => $e
        //     ]);
        //     // First batch job failure detected...
        // })->finally(function (Batch $batch1) {
        //     $result = DB::table('quin_error_log')->insert([
        //         'msg' => 'finally'
        //     ]);

        //     $batch2 = Bus::batch($direct_arr)->then(function (Batch $batch2) {
        //         // All jobs completed successfully...
        //     })->catch(function (Batch $batch2, Throwable $e) {
        //         // First batch job failure detected...
        //     })->finally(function (Batch $batch2) {

        //         $batch3 = Bus::batch($group_arr)->then(function (Batch $batch3) {
        //             // All jobs completed successfully...
        //         })->catch(function (Batch $batch3, Throwable $e) {
        //             // First batch job failure detected...
        //         })->finally(function (Batch $batch3) {

        //             $batch4 = Bus::batch([
        //                 $first_bd_arr,
        //                 $second_bd_arr
        //             ])->then(function (Batch $batch4) {
        //                 // All jobs completed successfully...
        //             })->catch(function (Batch $batch4, Throwable $e) {
        //                 // First batch job failure detected...
        //             })->finally(function (Batch $batch4) {
        //                 // The batch has finished executing...
        //             })->name('Daily Sales BD')->allowFailures()->dispatch();

        //         })->name('Daily Sales Group')->allowFailures()->dispatch();

        //     })->name('Daily Sales Direct Child')->allowFailures()->dispatch();

        // })->name('Daily Sales Individual')->allowFailures()->dispatch();

        // return $batch1->id;



        /////////////////////













        // return json_encode(CaptureDailySales::dispatch($start_date));
        // return json_encode(CaptureDirectChildrenSales::dispatch($start_date));

        // return json_encode(CaptureGroupSales::dispatch($start_date));
        // return json_encode(CaptureBDSales::dispatch($start_date));

        // return json_encode(CaptureLevelUpgrade::dispatch($start_date));

        // $start_date = '2021-12-02 00:00:00';
        // return json_encode(CaptureMonthlyCommissions::dispatch($start_date));
        // return json_encode(CaptureDirectChildrenCommissions::dispatch($start_date));

        // return json_encode(CaptureGroupCommissions::dispatch($start_date));
        // return json_encode(CaptureBDCommissions::dispatch($start_date));

        $start_date = '2021-12-01';
        $end_date = '2021-12-31';
        // return json_encode(CaptureMonthlyPayout::dispatch($start_date, $end_date));




        // $temp_user = QuinUser::where('referral_code', $ref_code)->first();
        // $start_date = '2021-12-02 00:00:00';
        // return $temp_user->recordPersonalSales($start_date);

        // $start_date = '2021-12-01 00:00:00';
        // return $temp_user->recordPersonalCommissions($start_date);

        // $start_date = '2021-12-01';
        // $end_date = '2021-12-31';
        // return $temp_user->recordMonthlyPayout($start_date, $end_date);
        // $be = [];
        // return $temp_user->calReferralSalesOverview($start_date, $end_date, $be);
        // $temp_user = QuinUser::where('referral_code', "BW216334")->first();
        // return $temp_user->getGroupCodes("BW216334", 1);

        // return $temp_user->calPersonalCommissions('2021-10-12 00:00:00');

        // return $temp_user->recordMonthlyPayout('2021-10-01 00:00:00', '2021-10-31 00:00:00');


        // return $temp_user->getBDsCode(1);

        // return $temp_user->calFirstLvlBDSalesOverview($start_date, $end_date);

        // return $temp_user->calPersonalSalesDetails($start_date, $end_date, $temp_user->getChildrenCode());

        // $arr = [$temp_user->referral_code];
        // return $temp_user->calPersonalSalesDetails($start_date, $end_date, $temp_user->getChildrenCode());

        // return $temp_user->calReferralSales($start_date, $end_date, $temp_user->getChildrenCode())->current_sales;

        // return $temp_user->getChildrenCode();
        // return sizeof($arr);

    }

}
