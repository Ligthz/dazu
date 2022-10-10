<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\QuinRolesHistory;
use App\Models\QuinUser;
use Carbon\Carbon;

class SummaryController extends Controller
{



    /**
     * Display the specified resource.
     * @param  int  $key
     * @return \Illuminate\Http\Response
     */
    public function personalStatistic ($key)
    {
        $date = formatDateTimeZone(Carbon::now(), 1);

        $start_date = date_format(date_create($date), "Y-m-01 00:00:00");
        $end_date = $date;


        // calculate compared date
        $compared_to_start_date = date_create($start_date);
        date_sub($compared_to_start_date, date_interval_create_from_date_string("1 month"));

        $compared_to_start_date = date_format($compared_to_start_date, "Y-m-d H:i:s");
        $compared_to_end_date = date("Y-m-t 23:59:59", strtotime($compared_to_start_date));


        // personal sales, orders, products, customers
        $subQuery = DB::table('quin_order_item_meta')->select(
            DB::raw('CAST('.DB::getTablePrefix().'quin_order_item_meta.date_created AS DATE) as purchased_date'),
            DB::raw('ROUND(SUM('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 2) as accumulate_sales'),
            DB::raw('COUNT(DISTINCT '.DB::getTablePrefix().'wc_order_stats.order_id) as accumulate_orders'),
            DB::raw('sum('.DB::getTablePrefix().'quin_order_item_meta.product_qty) as accumulate_products'),
            DB::raw('COUNT(DISTINCT '.DB::getTablePrefix().'quin_order_item_meta.customer_id) as accumulate_customers'),
        )
        ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
        ->join('quin_users_meta', function ($join) {
            $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
        })
        ->where('quin_users_meta.users_key', $key)
        ->where('quin_order_item_meta.date_created', '>=', $start_date)
        ->where('quin_order_item_meta.date_created', '<=', $end_date)
        ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')")
        ->groupBy('purchased_date');
        //echo $subQuery->toSql();
        $result = DB::table(DB::raw('('.$subQuery->toSql().') as '.DB::getTablePrefix().'personal_result'))->select(
            DB::raw(DB::getTablePrefix().'dates.Date as dates'),
            DB::raw('COALESCE('.DB::getTablePrefix().'personal_result.accumulate_sales, 0) as personal_sales_amount'),
            DB::raw('COALESCE('.DB::getTablePrefix().'personal_result.accumulate_orders, 0) as personal_orders_amount'),
            DB::raw('COALESCE('.DB::getTablePrefix().'personal_result.accumulate_products, 0) as personal_products_amount'),
            DB::raw('COALESCE('.DB::getTablePrefix().'personal_result.accumulate_customers, 0) as personal_customers_amount')
        )
        ->rightJoin(DB::raw("
            (select Date from
            (select adddate('2021-01-01', t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) Date
            from (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
                    (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
                    (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t2,
                    (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6) t3,
                    (select 0 i union select 1 union select 2 union select 3) t4) v
            where CAST(Date AS DATE) between '$start_date' and '$end_date') as ".DB::getTablePrefix()."dates"
            ), 'personal_result.purchased_date', '=', 'dates.Date'
        )
        ->orderBy('dates.Date', 'asc')
        ->mergeBindings($subQuery);



        // current total personal sales, orders, products, customers
        $result_2_current = DB::table('quin_order_item_meta')->select(
            DB::raw('COALESCE(ROUND(sum('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 2), 0) as total_personal_sales'),
            DB::raw('COALESCE(COUNT(DISTINCT '.DB::getTablePrefix().'wc_order_stats.order_id), 0) as total_personal_orders'),
            DB::raw('COALESCE(sum('.DB::getTablePrefix().'quin_order_item_meta.product_qty), 0) as total_personal_products'),
            DB::raw('COALESCE(COUNT(DISTINCT '.DB::getTablePrefix().'quin_order_item_meta.customer_id), 0) as total_personal_customers')
        )
        ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
        ->join('quin_users_meta', function ($join) {
            $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
        })
        ->where('quin_users_meta.users_key', $key)
        ->where('quin_order_item_meta.date_created', '>=', $start_date)
        ->where('quin_order_item_meta.date_created', '<=', $end_date)
        ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')");

        // past total personal sales, orders, products, customers
        $result_2_past = DB::table('quin_order_item_meta')->select(
            DB::raw('COALESCE(ROUND(sum('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 2), 0) as total_personal_sales'),
            DB::raw('COALESCE(COUNT(DISTINCT '.DB::getTablePrefix().'wc_order_stats.order_id), 0) as total_personal_orders'),
            DB::raw('COALESCE(sum('.DB::getTablePrefix().'quin_order_item_meta.product_qty), 0) as total_personal_products'),
            DB::raw('COALESCE(COUNT(DISTINCT '.DB::getTablePrefix().'quin_order_item_meta.customer_id), 0) as total_personal_customers')
        )
        ->join('wc_order_stats', 'quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
        ->join('quin_users_meta', function ($join) {
            $join->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                ->whereRaw("(".DB::getTablePrefix()."quin_order_item_meta.customer_id = ".DB::getTablePrefix()."quin_users_meta.users_id OR ".DB::getTablePrefix()."quin_order_item_meta.sold_by = ".DB::getTablePrefix()."quin_users_meta.referral_code)");
        })
        ->where('quin_users_meta.users_key', $key)
        ->where('quin_order_item_meta.date_created', '>=', $compared_to_start_date)
        ->where('quin_order_item_meta.date_created', '<=', $compared_to_end_date)
        ->whereRaw("(".DB::getTablePrefix()."wc_order_stats.status = 'wc-processing' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-shipping' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-completed' or ".DB::getTablePrefix()."wc_order_stats.status = 'wc-delivered')");


        $result = $result->get();
        $result_2_current = $result_2_current->get();
        $result_2_past = $result_2_past->get();

        $current_sales = (float) $result_2_current[0]->total_personal_sales;
        $past_sales = (float) $result_2_past[0]->total_personal_sales;

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


        return response([
            'charts' => $result,
            'numbers' => $result_2_current,
            'percent_sales' => abs(round($sales_percent, 0)) . '%',
            'status_sales' => (int) $sales_stat
        ], 200);
    }
}
