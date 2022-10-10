<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\QuinUser;
use App\Models\PayoutItem;
use App\Models\SettlementItem;

class PayoutDetailsController extends Controller
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
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $user = Auth::user();

        try{
            $current_quin_user = QuinUser::where('users_id', $user->ID)->firstOrFail();

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response([
                "error" => [
                    "code"=>"POD001",
                    "message"=>"User not found!"
                ]
            ], 404);
        }


        try{
            $payout = DB::table('quin_monthly_payout')->select(
                'payout_id',
                'partner_id',
                DB::raw(DB::getTablePrefix().'quin_monthly_payout.created_at as payout_date'),
                'amount',
                'account_details',
                'payout_details',
                DB::raw('
                    (case when '.DB::getTablePrefix().'quin_monthly_payout.status = 0 then "Failed"
                        when '.DB::getTablePrefix().'quin_monthly_payout.status = 1 then "Pending"
                        when '.DB::getTablePrefix().'quin_monthly_payout.status = 2 then "Processing"
                        when '.DB::getTablePrefix().'quin_monthly_payout.status = 3 then "Paid"
                        else '.DB::getTablePrefix().'quin_monthly_payout.status
                    end) as payout_status
                ')
            )
            ->where('payout_id', '=', $id)
            ->where('partner_id', '=', $current_quin_user->referral_code)
            ->first();

        } catch(\Illuminate\Database\QueryException $ex){
            return response([
                "error" => [
                    "code"=>"POD002",
                    "message"=>"Resource not found!"
                ]
            ], 404);
        }

        $acc = json_decode($payout->account_details);
        $pay = json_decode($payout->payout_details);


        $payout_arr = array();
        $hasBD = false;
        $settlement_total = 0;
        $settlement_personal_vol_total = 0;
        $settlement_first_bd_total = 0;
        $settlement_second_bd_total = 0;


        foreach($pay->payout as $rec) {
            $total = 0;
            $payout_rows = array();

            // personal + referral
            $personal = new PayoutItem();
            $personal->id = ($rec->role * 100) + 10 + 1;
            $personal->name = 'Self Purchase';
            $personal->sales = (double) $rec->personal_sales;
            $personal->rate = (double) $rec->personal_bonus;
            $personal->total = (double) $rec->personal_commissions;
            $personal->expand = [];

            $referral = new PayoutItem();
            $referral->id = ($rec->role * 100) + 10 + 2;
            $referral->name = 'Referral';
            $referral->sales = (double) $rec->referral_sales;
            $referral->rate = (double) $rec->referral_bonus;
            $referral->total = (double) $rec->referral_commissions;
            $referral->expand = [];

            $summary_personal = new PayoutItem();
            $summary_personal->id = ($rec->role * 100) + 10;
            $summary_personal->name = 'Personal';
            $summary_personal->sales = ((double) $rec->personal_sales) + ((double) $rec->referral_sales);
            $summary_personal->rate = '';
            $summary_personal->total = ((double) $rec->personal_commissions) + ((double) $rec->referral_commissions);
            $summary_personal->expand = array($personal, $referral);

            $total += (double) $summary_personal->total;


            // direct child
            $dir_ba = new PayoutItem();
            $dir_ba->id = ($rec->role * 100) + 20 + 1;
            $dir_ba->name = 'Direct Child (TG)';
            $dir_ba->sales = (double) $rec->dir_ba_sales;
            $dir_ba->rate = (double) $rec->dir_ba_bonus;
            $dir_ba->total = (double) $rec->dir_ba_commissions;
            $dir_ba->expand = [];

            $dir_be = new PayoutItem();
            $dir_be->id = ($rec->role * 100) + 20 + 2;
            $dir_be->name = 'Direct Child (TE)';
            $dir_be->sales = (double) $rec->dir_be_sales;
            $dir_be->rate = (double) $rec->dir_be_bonus;
            $dir_be->total = (double) $rec->dir_be_commissions;
            $dir_be->expand = [];

            $dir_bm = new PayoutItem();
            $dir_bm->id = ($rec->role * 100) + 20 + 3;
            $dir_bm->name = 'Direct Child (TP)';
            $dir_bm->sales = (double) $rec->dir_bm_sales;
            $dir_bm->rate = (double) $rec->dir_bm_bonus;
            $dir_bm->total = (double) $rec->dir_bm_commissions;
            $dir_bm->expand = [];

            $dir_bd = new PayoutItem();
            $dir_bd->id = ($rec->role * 100) + 20 + 4;
            $dir_bd->name = 'Direct Child (TM)';
            $dir_bd->sales = (double) $rec->dir_bd_sales;
            $dir_bd->rate = (double) $rec->dir_bd_bonus;
            $dir_bd->total = (double) $rec->dir_bd_commissions;
            $dir_bd->expand = [];

            $summary_direct = new PayoutItem();
            $summary_direct->id = ($rec->role * 100) + 20;
            $summary_direct->name = 'Direct Child';
            $summary_direct->sales = ((double) $rec->dir_ba_sales) + ((double) $rec->dir_be_sales) + ((double) $rec->dir_bm_sales) + ((double) $rec->dir_bd_sales);
            $summary_direct->rate = '';
            $summary_direct->total = ((double) $rec->dir_ba_commissions) + ((double) $rec->dir_be_commissions) + ((double) $rec->dir_bm_commissions) + ((double) $rec->dir_bd_commissions);
            $summary_direct->expand = array($dir_ba, $dir_be, $dir_bm, $dir_bd);

            $total += (double) $summary_direct->total;

            array_push($payout_rows, $summary_personal);
            array_push($payout_rows, $summary_direct);


            if($rec->role == 4) {

                $hasBD = true;

                // group
                $summary_personal_volume = new PayoutItem();
                $summary_personal_volume->id = ($rec->role * 100) + 30;
                $summary_personal_volume->name = 'Personal Volume';
                $summary_personal_volume->sales = (double) $rec->personal_vol_sales;
                $summary_personal_volume->rate = (double) $rec->personal_vol_bonus;
                $summary_personal_volume->total = (double) $rec->personal_vol_commissions;
                $summary_personal_volume->expand = [];

                $total += (double) $summary_personal_volume->total;
                $settlement_personal_vol_total += (double) $summary_personal_volume->total;

                // first bd
                $summary_first_bd = new PayoutItem();
                $summary_first_bd->id = ($rec->role * 100) + 40 + 1;
                $summary_first_bd->name = 'First Group Volume';
                $summary_first_bd->sales = (double) $rec->first_bd_sales;
                $summary_first_bd->rate = (double) $rec->first_bd_bonus;
                $summary_first_bd->total = (double) $rec->first_bd_commissions;
                $summary_first_bd->expand = [];

                $total += (double) $summary_first_bd->total;
                $settlement_first_bd_total += (double) $summary_first_bd->total;

                // second bd
                $summary_second_bd = new PayoutItem();
                $summary_second_bd->id = ($rec->role * 100) + 40 + 2;
                $summary_second_bd->name = 'Second Group Volume';
                $summary_second_bd->sales = (double) $rec->second_bd_sales;
                $summary_second_bd->rate = (double) $rec->second_bd_bonus;
                $summary_second_bd->total = (double) $rec->second_bd_commissions;
                $summary_second_bd->expand = [];

                $total += (double) $summary_second_bd->total;
                $settlement_second_bd_total += (double) $summary_second_bd->total;

                array_push($payout_rows, $summary_personal_volume);
                array_push($payout_rows, $summary_first_bd);
                array_push($payout_rows, $summary_second_bd);
            }

            $payout_rec = array(
                "role" => $rec->role,
                "subtotal" => $total,
                "details" => $payout_rows
            );

            array_push($payout_arr, $payout_rec);

            $settlement_total += $total;
        }

        $settlement_arr = array();

        $settlement_original = new SettlementItem;
        $settlement_original->name = 'Payout Subtotal';
        $settlement_original->type = '';
        $settlement_original->total = $settlement_total;

        array_push($settlement_arr, $settlement_original);

        if($pay->kpi->monthly_basic_kpi->monthly_kpi_hit == 0) {
            $deduct_all = new SettlementItem;
            $deduct_all->name = '*Failed to reach Minimum Monthly KPI';
            $deduct_all->type = '-';
            $deduct_all->total = $settlement_total;

            array_push($settlement_arr, $deduct_all);
        }
        else {
            if($hasBD == true) {
                if($pay->kpi->bd_kpi->personal_vol_kpi_hit == 0) {
                    $deduct_personal_volume = new SettlementItem;
                    $deduct_personal_volume->name = '*Failed to reach Personal Volume KPI';
                    $deduct_personal_volume->type = '-';
                    $deduct_personal_volume->total = $settlement_personal_vol_total;

                    array_push($settlement_arr, $deduct_personal_volume);
                }

                if($pay->kpi->bd_kpi->first_bd_kpi_hit == 0) {
                    $deduct_first_bd = new SettlementItem;
                    $deduct_first_bd->name = '*Failed to reach First Group Volume KPI';
                    $deduct_first_bd->type = '-';
                    $deduct_first_bd->total = $settlement_first_bd_total;

                    array_push($settlement_arr, $deduct_first_bd);
                }

                if($pay->kpi->bd_kpi->second_bd_kpi_hit == 0) {
                    $deduct_second_bd = new SettlementItem;
                    $deduct_second_bd->name = '*Failed to reach Second Group Volume KPI';
                    $deduct_second_bd->type = '-';
                    $deduct_second_bd->total = $settlement_second_bd_total;

                    array_push($settlement_arr, $deduct_second_bd);
                }
            }
        }

        return response([
            "payout_id" => $payout->payout_id,
            "user" => $payout->partner_id,
            "date" => $payout->payout_date,
            "start_date" => $pay->start_date,
            "end_date" => $pay->end_date,
            "amount" => $payout->amount,
            "status" => $payout->payout_status,
            "account_details" => $acc,
            "payout_details" => $payout_arr,
            "settlement" => $settlement_arr
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
