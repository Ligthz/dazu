<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

use function PHPSTORM_META\type;

class QuinUser extends Model
{
    use HasFactory;

    protected $table = "quin_users_meta";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'users_key',
        'username',
        'fname',
        'lname',
        'contact',
        'address',
        'dob',
        'gender',
        'race',
        'marital_status',
        'avatar',
        'ic_passport_no',
        'bank_name',
        'bank_account_no',
        'bank_account_name',
        'mentor_id',
        'partner_joined_at',
        'referral_code',
        'status',
        'last_login'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'users_id'
    ];

    private $nonBDGroup = array();
    private $BDGroup = array();
    private $allowStatus = array(21,23);

    // relation to predefined users table
    public function connectedUsers(){
        return $this->hasOne(User::class, 'ID', 'users_id');
    }

    // relation to self-defined roles table
    public function connectedQuinRolesHistories(){
        return $this->hasMany(QuinRolesHistory::class, 'users_id', 'users_id');
    }

    // relation to avatar file table
    public function connectedFile(){
        return $this->hasOne(File::class, 'id', 'avatar');
    }

    // relation to quin bank table
    public function connectedBank(){
        return $this->hasOne(QuinBank::class, 'bank_id', 'bank_name');
    }

    public function getReferral(){
        return $this->referral_code;
    }

    public function getMentor(){
        return $this->mentor_id;
    }

    public function getAllowStatus(){
        return $this->allowStatus;
    }

    public function getBDLevel($target_mentor){
        $level = 1;
        $mentor_code = $this->getMentor();
        if($this->getRole() == 4){
            while($target_mentor != $mentor_code){
                $new_mentor = QuinUser::where('referral_code', $mentor_code)->first();
                $mentor_code = $new_mentor->getMentor();
                if($new_mentor->getRole() == 4){
                    $level++;
                }
            }
        }

        return $level;
    }

    public function getBDs($level = 0, $refcode = null, $date = null){

        if($date != null) {
            $subquery = DB::table("quin_roles_history_meta")
            ->select('users_id', DB::raw('max(created_at) as role_date'))
            ->where('created_at', '<', $date)
            ->groupBy('users_id');
        }
        else {
            $subquery = DB::table("quin_roles_history_meta")
            ->select('users_id', DB::raw('max(created_at) as role_date'))
            ->where('created_at', '<', formatDateTimeZone(date('Y-m-d H:i:s'), 1))
            ->groupBy('users_id');
        }


        //filter BD
        $roles_data = DB::table('quin_roles_history_meta')
        ->select('quin_roles_history_meta.users_id', 'quin_roles_history_meta.roles',
            DB::raw(DB::getTablePrefix().'quin_roles_meta.short_name as roles_name'),
            DB::raw(DB::getTablePrefix().'quin_roles_meta.primary_color as roles_color')
        )
        ->joinSub($subquery,'role_his', function ($join) {
            $join->on('quin_roles_history_meta.users_id', '=', 'role_his.users_id')
            ->on('quin_roles_history_meta.created_at', '=', 'role_his.role_date')
            ->where('quin_roles_history_meta.roles', '>=', 1)
            ->where('quin_roles_history_meta.roles', '<=', 4);
        })
        ->join('quin_roles_meta', 'quin_roles_history_meta.roles', '=', 'quin_roles_meta.id');

        if($refcode == null){
            $this->BDGroup = [];

            $user_data = DB::table('quin_users_meta')
            ->select('quin_users_meta.*',
                'roles_data.*',
                DB::raw(DB::getTablePrefix().'quin_files_meta.name as avatar_name'),
                DB::raw(DB::getTablePrefix().'quin_files_meta.path as avatar_path')
            )
            ->joinSub($roles_data, 'roles_data', function ($join) {
                $join->on('quin_users_meta.users_id', '=', 'roles_data.users_id');
            })
            ->leftJoin('quin_files_meta', 'quin_users_meta.avatar', '=', 'quin_files_meta.id')
            ->where('quin_users_meta.mentor_id','=',$this->referral_code)
            ->get();
        }else{
            $user_data = DB::table('quin_users_meta')
            ->select('quin_users_meta.*',
                'roles_data.*',
                DB::raw(DB::getTablePrefix().'quin_files_meta.name as avatar_name'),
                DB::raw(DB::getTablePrefix().'quin_files_meta.path as avatar_path')
            )
            ->joinSub($roles_data, 'roles_data', function ($join) {
                $join->on('quin_users_meta.users_id', '=', 'roles_data.users_id');
            })
            ->leftJoin('quin_files_meta', 'quin_users_meta.avatar', '=', 'quin_files_meta.id')
            ->where('quin_users_meta.mentor_id','=',$refcode)
            ->get();
        }

        foreach($user_data as $child){
            if($child->roles == 4){
                $temp_partner = QuinUser::where('referral_code', $child->referral_code)->first();
                $bdLevel = $temp_partner->getBDLevel($this->referral_code);
                $child->bdlevel = $bdLevel;
                if($level == 1 && $bdLevel == 1){
                    array_push($this->BDGroup,$child);
                }else if($level == 2 && $bdLevel == 2){
                    array_push($this->BDGroup,$child);
                }else if($bdLevel >= 1 && $bdLevel <= 2 && $level == 0){
                    array_push($this->BDGroup,$child);
                }

            }
            $this->getBDs($level, $child->referral_code, $date);
        }
        return $this->BDGroup;
    }

    public function getBDsCode($level = 0, $date = null){
        $grp_ids = array();
        $group = $this->getBDs($level, null, $date);
        foreach($group as $child){
            array_push($grp_ids,$child->referral_code);
        }
        return $grp_ids;
    }

    public function getChildren(){
        $children = QuinUser::where('mentor_id',$this->referral_code)->get();
        return $children;
    }

    public function getChildrenCode(){
        $children = QuinUser::select('referral_code')->where('mentor_id',$this->referral_code)->get();
        $children_arr = array();
        foreach($children as $child){
            array_push($children_arr, $child->referral_code);
        }
        return $children_arr;
    }

    public function getGroup($refcode = null, $date = null){

        if($date != null) {
            $subquery = DB::table("quin_roles_history_meta")
            ->select('users_id', DB::raw('max(created_at) as role_date'))
            ->where('created_at', '<', $date)
            ->groupBy('users_id');
        }
        else {
            $subquery = DB::table("quin_roles_history_meta")
            ->select('users_id', DB::raw('max(created_at) as role_date'))
            ->where('created_at', '<', formatDateTimeZone(date('Y-m-d H:i:s'), 1))
            ->groupBy('users_id');
        }

        //filter non BD
        $roles_data = DB::table('quin_roles_history_meta')
        ->select('quin_roles_history_meta.users_id', 'quin_roles_history_meta.roles',
            DB::raw(DB::getTablePrefix().'quin_roles_meta.short_name as roles_name'),
            DB::raw(DB::getTablePrefix().'quin_roles_meta.primary_color as roles_color')
        )
        ->joinSub($subquery,'role_his', function ($join) {
            $join->on('quin_roles_history_meta.users_id', '=', 'role_his.users_id')
            ->on('quin_roles_history_meta.created_at', '=', 'role_his.role_date')
            ->where('quin_roles_history_meta.roles', '>=', 1)
            ->where('quin_roles_history_meta.roles', '<=', 3);
        })
        ->join('quin_roles_meta', 'quin_roles_history_meta.roles', '=', 'quin_roles_meta.id');

        if($refcode == null){
            $this->nonBDGroup = [];
            $user_data = DB::table('quin_users_meta')
            ->select('quin_users_meta.*',
                'roles_data.*',
                DB::raw(DB::getTablePrefix().'quin_files_meta.name as avatar_name'),
                DB::raw(DB::getTablePrefix().'quin_files_meta.path as avatar_path')
            )
            ->joinSub($roles_data, 'roles_data', function ($join) {
                $join->on('quin_users_meta.users_id', '=', 'roles_data.users_id');
            })
            ->leftJoin('quin_files_meta', 'quin_users_meta.avatar', '=', 'quin_files_meta.id')
            ->where('quin_users_meta.mentor_id','=',$this->referral_code)
            ->get();
        }else{
            $user_data = DB::table('quin_users_meta')
            ->select('quin_users_meta.*',
                'roles_data.*',
                DB::raw(DB::getTablePrefix().'quin_files_meta.name as avatar_name'),
                DB::raw(DB::getTablePrefix().'quin_files_meta.path as avatar_path')
            )
            ->joinSub($roles_data, 'roles_data', function ($join) {
                $join->on('quin_users_meta.users_id', '=', 'roles_data.users_id');
            })
            ->leftJoin('quin_files_meta', 'quin_users_meta.avatar', '=', 'quin_files_meta.id')
            ->where('quin_users_meta.mentor_id','=',$refcode)
            ->get();
        }
        foreach($user_data as $child){
            array_push($this->nonBDGroup,$child);
            $this->getGroup($child->referral_code, $date);
        }
        return $this->nonBDGroup;
    }

    public function getGroupCodes($date = null){
        $grp_ids = array();
        $group = $this->getGroup(null, $date);
        foreach($group as $child){
            array_push($grp_ids,$child->referral_code);
        }
        return $grp_ids;
    }

    public function getRole($date = null){

        if($date != null) {
            $his = QuinRolesHistory::where([
                ['users_id',  '=', $this->users_id],
                ['created_at', '<', $date]
            ])->orderByDesc('created_at')->first();
        }
        else {
            $his = QuinRolesHistory::where([
                ['users_id',  '=', $this->users_id],
                ['created_at', '<', formatDateTimeZone(date('Y-m-d H:i:s'), 1)]
            ])->orderByDesc('created_at')->first();
        }

        return $his->roles;
    }

    public function getUpgradeReq($date = null){
        $upgradeReq = QuinRoles::where('id', $this->getRole($date))->first();
        return $upgradeReq;
    }

    //save personal & referral sales into db
    public function recordPersonalSales($date){

        $personal_sales_arr = $this->calPersonalSales($date);
        $referral_sales_arr = $this->calReferralSales($date);

        $order_item_ids = array_merge($personal_sales_arr['unclaimed_order_item_ids'], $referral_sales_arr['unclaimed_order_item_ids']);


        $daily_sales = new DailySales;
        $daily_sales->id = date('ymd', strtotime("-1 day", strtotime($date))).$this->referral_code.Str::random(18);
        $daily_sales->date = date("Y-m-d", strtotime("-1 day", strtotime($date)));
        $daily_sales->users_key = $this->users_key;
        $daily_sales->referral_code = $this->referral_code;
        $daily_sales->role = (int) $personal_sales_arr['personal_sales']->role;

        $daily_sales->personal_sales = (float) $personal_sales_arr['personal_sales']->personal_sales;
        $daily_sales->personal_bonus = (float) $personal_sales_arr['personal_sales']->personal_bonus;
        $daily_sales->personal_commissions = (float) $personal_sales_arr['personal_sales']->personal_commissions;

        $daily_sales->referral_sales = (float) $referral_sales_arr['referral_sales']->referral_sales;
        $daily_sales->referral_bonus = (float) $referral_sales_arr['referral_sales']->referral_bonus;
        $daily_sales->referral_commissions = (float) $referral_sales_arr['referral_sales']->referral_commissions;

        try{
            $exist_data = DailySales::where([
                ['referral_code', $this->referral_code],
                ['date', date("Y-m-d", strtotime("-1 day", strtotime($date)))]
            ])
            ->first();

            if($exist_data == null) {
                if($daily_sales->save()) {

                    $update_result = DB::table('quin_order_item_meta')
                        ->whereIn('order_item_id', $order_item_ids)
                        ->update(['daily_sales_id' => $daily_sales->id]);

                    return array('status'=>true);
                }
            }
            else {
                DailySales::where([
                    ['referral_code', '=', $this->referral_code],
                    ['date', '=', date("Y-m-d", strtotime("-1 day", strtotime($date)))]
                ])->update([
                    'role' => (int) $personal_sales_arr['personal_sales']->role,
                    'personal_sales' => (float) $personal_sales_arr['personal_sales']->personal_sales,
                    'personal_bonus' => (float) $personal_sales_arr['personal_sales']->personal_bonus,
                    'personal_commissions' => (float) $personal_sales_arr['personal_sales']->personal_commissions,
                    'referral_sales' => (float) $referral_sales_arr['referral_sales']->referral_sales,
                    'referral_bonus' => (float) $referral_sales_arr['referral_sales']->referral_bonus,
                    'referral_commissions' => (float) $referral_sales_arr['referral_sales']->referral_commissions
                ]);


                $update_result = DB::table('quin_order_item_meta')
                    ->whereIn('order_item_id', $order_item_ids)
                    ->update(['daily_sales_id' => $exist_data->id]);

                return array('status'=>true);
            }
        }catch(\Illuminate\Database\QueryException $ex){
            if($ex->getCode() == '23000'){
                return array('status'=>false,'ex'=>$ex);
            }
        }
    }

    public function calPersonalSales($date) {
        $date = date_format(date_create($date), "Y-m-d 00:00:00");

        $role = (int) $this->getRole($date);
        $unclaimed_order_item_ids = array();


        // get unclaimed order items
        $order_item_ids = DB::table('quin_order_item_meta')->select(
            'quin_order_item_meta.order_item_id'
        )
        ->distinct()
        ->join('wc_order_stats', function($join) {
            $join->on('quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
                ->whereIn('wc_order_stats.status',array("wc-processing","wc-shipping","wc-delivered","wc-completed"));
        })
        ->join('quin_users_meta', function($join) {
            $join->on('quin_order_item_meta.customer_id', '=', 'quin_users_meta.users_id')
                ->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                ->where('quin_users_meta.users_key', '=', $this->users_key);
        })
        ->where(function($query) {
            $query->whereNull('quin_order_item_meta.daily_sales_id')
                ->whereNull('quin_order_item_meta.monthly_coms_id');
        })
        ->where('quin_order_item_meta.date_created', '<', $date)
        ->get();

        foreach($order_item_ids as $ids) {
            array_push($unclaimed_order_item_ids, (int) $ids->order_item_id);
        }

        //get personal sales
        $personal_sales = DB::table('quin_users_meta')->select(
            'quin_users_meta.referral_code',
            DB::raw(DB::getTablePrefix().'quin_roles_meta.id as role'),
            DB::raw("(coalesce(".DB::getTablePrefix()."quin_roles_meta.referral_amount, 0) - coalesce(".DB::getTablePrefix()."quin_options.option_value, 0)) as personal_bonus"),
            DB::raw('coalesce(sum('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 0) as personal_sales'),
            DB::raw("(coalesce(".DB::getTablePrefix()."quin_roles_meta.referral_amount, 0) - coalesce(".DB::getTablePrefix()."quin_options.option_value, 0)) * coalesce(sum(".DB::getTablePrefix()."quin_order_item_meta.product_subtotal), 0) as personal_commissions")
        )
        ->leftJoin('quin_roles_meta', function($join) use ($role) {
            $join->where('quin_roles_meta.id', '=', $role);
        })
        ->leftJoin('quin_options',  function($join) {
            $join->where('quin_options.option_name', '=', 'partner_discount');
        })
        ->leftJoin('quin_order_item_meta', function($join) use ($unclaimed_order_item_ids) {
            $join->on('quin_users_meta.users_id', '=', 'quin_order_item_meta.customer_id')
                ->whereIn('quin_order_item_meta.order_item_id', $unclaimed_order_item_ids);
        })
        ->where('quin_users_meta.users_key', $this->users_key)
        ->groupBy('quin_users_meta.referral_code', 'quin_roles_meta.id', 'quin_roles_meta.referral_amount', 'quin_options.option_value')
        ->first();

        return array(
            'unclaimed_order_item_ids' => $unclaimed_order_item_ids,
            'personal_sales' => $personal_sales
        );
    }


    public function calReferralSales($date) {
        $date = date_format(date_create($date), "Y-m-d 00:00:00");

        $role = (int) $this->getRole($date);
        $unclaimed_order_item_ids = array();


        // get unclaimed order items
        $order_item_ids = DB::table('quin_order_item_meta')->select(
            'quin_order_item_meta.order_item_id'
        )
        ->distinct()
        ->join('wc_order_stats', function($join) {
            $join->on('quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
                ->whereIn('wc_order_stats.status',array("wc-processing","wc-shipping","wc-delivered","wc-completed"));
        })
        ->join('quin_users_meta', function($join) {
            $join->on('quin_order_item_meta.sold_by', '=', 'quin_users_meta.referral_code')
                ->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                ->where('quin_users_meta.users_key', '=', $this->users_key);
        })
        ->where(function($query) {
            $query->whereNull('quin_order_item_meta.daily_sales_id')
                ->whereNull('quin_order_item_meta.monthly_coms_id');
        })
        ->where('quin_order_item_meta.date_created', '<', $date)
        ->get();

        foreach($order_item_ids as $ids) {
            array_push($unclaimed_order_item_ids, (int) $ids->order_item_id);
        }



        //get referral sales
        $referral_sales = DB::table('quin_users_meta')->select(
            'quin_users_meta.referral_code',
            DB::raw(DB::getTablePrefix().'quin_roles_meta.id as role'),
            DB::raw("coalesce(".DB::getTablePrefix()."quin_roles_meta.referral_amount, 0) as referral_bonus"),
            DB::raw('coalesce(sum('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 0) as referral_sales'),
            DB::raw("coalesce(".DB::getTablePrefix()."quin_roles_meta.referral_amount, 0) * coalesce(sum(".DB::getTablePrefix()."quin_order_item_meta.product_subtotal), 0) as referral_commissions")
        )
        ->leftJoin('quin_roles_meta', function($join) use ($role) {
            $join->where('quin_roles_meta.id', '=', $role);
        })
        ->leftJoin('quin_order_item_meta', function($join) use ($unclaimed_order_item_ids) {
            $join->on('quin_users_meta.referral_code', '=', 'quin_order_item_meta.sold_by')
                ->whereIn('quin_order_item_meta.order_item_id', $unclaimed_order_item_ids);
        })
        ->where('quin_users_meta.users_key', $this->users_key)
        ->groupBy('quin_users_meta.referral_code', 'quin_roles_meta.id', 'quin_roles_meta.referral_amount')
        ->first();

        return array(
            'unclaimed_order_item_ids' => $unclaimed_order_item_ids,
            'referral_sales' => $referral_sales
        );
    }


    // save direct child sales & commissions into db
    public function recordDirectSales($date){
        $date = date_format(date_create($date), "Y-m-d 00:00:00");

        $role = (int) $this->getRole($date);

        $direct_count = array(0,0,0,0);
        $direct_sales = array(0,0,0,0);
        $direct_bonus = array(0,0,0,0);
        $direct_commissions = array(0,0,0,0);

        // get direct bonus
        $bonus_data = DB::table('quin_roles_meta')
        ->select('direct_ba_bonus', 'direct_be_bonus', 'direct_bm_bonus', 'direct_bd_bonus')
        ->where('id', $role)
        ->first();

        $direct_bonus[0] = (float) $bonus_data->direct_ba_bonus;
        $direct_bonus[1] = (float) $bonus_data->direct_be_bonus;
        $direct_bonus[2] = (float) $bonus_data->direct_bm_bonus;
        $direct_bonus[3] = (float) $bonus_data->direct_bd_bonus;


        //get user current role
        $subquery = DB::table("quin_roles_history_meta")
        ->select('users_id', DB::raw('max(created_at) as role_date'))
        ->where('created_at', '<', $date)
        ->groupBy('users_id');

        $roles_data = DB::table('quin_roles_history_meta')
        ->select('quin_roles_history_meta.users_id', 'quin_roles_history_meta.roles')
        ->joinSub($subquery,'role_his', function ($join) {
            $join->on('quin_roles_history_meta.users_id', '=', 'role_his.users_id')
            ->on('quin_roles_history_meta.created_at', '=', 'role_his.role_date');
        })
        ->orderBy('quin_roles_history_meta.users_id');

        //calculate data
        foreach($direct_count as $count => $value){
            $direct_count[$count] = DB::table('quin_daily_sales')
            ->select('quin_users_meta.referral_code','quin_users_meta.mentor_id','roles_data.roles')
            ->join('quin_users_meta','quin_daily_sales.users_key','=','quin_users_meta.users_key')
            ->joinSub($roles_data, 'roles_data', function ($join) {
                $join->on('quin_users_meta.users_id', '=', 'roles_data.users_id');
            })
            ->whereIn('quin_users_meta.status', $this->allowStatus)
            ->where('quin_users_meta.mentor_id','=',$this->referral_code)
            ->where('roles_data.roles','=',$count+1)
            ->where('quin_daily_sales.date','=',date("Y-m-d", strtotime("-1 day", strtotime($date))))
            ->count();


            if($count == 0) {
                $direct_sales_amount = DB::table('quin_daily_sales')->select(
                    DB::raw('COALESCE(SUM(personal_sales + referral_sales),0) as direct_sales'),
                    DB::raw("(COALESCE(SUM(personal_sales + referral_sales),0) * ".DB::getTablePrefix()."quin_roles_meta.direct_ba_bonus) as direct_commissions")
                )
                ->join('quin_users_meta','quin_daily_sales.users_key','=','quin_users_meta.users_key')
                ->join('quin_roles_meta',
                    function($join) use ($role) {
                        $join->where("quin_roles_meta.id", '=', $role);
                    }
                )
                ->joinSub($roles_data, 'roles_data', function ($join) {
                    $join->on('quin_users_meta.users_id', '=', 'roles_data.users_id');
                })
                ->whereIn('quin_users_meta.status', $this->allowStatus)
                ->where('quin_users_meta.mentor_id','=',$this->referral_code)
                ->where('roles_data.roles','=', $count+1)
                ->where('quin_daily_sales.date','=',date("Y-m-d", strtotime("-1 day", strtotime($date))))
                ->groupBy('direct_ba_bonus');
            }
            else if($count == 1) {
                $direct_sales_amount = DB::table('quin_daily_sales')->select(
                    DB::raw('COALESCE(SUM(personal_sales + referral_sales),0) as direct_sales'),
                    DB::raw("(COALESCE(SUM(personal_sales + referral_sales),0) * ".DB::getTablePrefix()."quin_roles_meta.direct_be_bonus) as direct_commissions")
                )
                ->join('quin_users_meta','quin_daily_sales.users_key','=','quin_users_meta.users_key')
                ->join('quin_roles_meta',
                    function($join) use ($role) {
                        $join->where("quin_roles_meta.id", '=', $role);
                    }
                )
                ->joinSub($roles_data, 'roles_data', function ($join) {
                    $join->on('quin_users_meta.users_id', '=', 'roles_data.users_id');
                })
                ->whereIn('quin_users_meta.status', $this->allowStatus)
                ->where('quin_users_meta.mentor_id','=',$this->referral_code)
                ->where('roles_data.roles','=', $count+1)
                ->where('quin_daily_sales.date','=',date("Y-m-d", strtotime("-1 day", strtotime($date))))
                ->groupBy('direct_be_bonus');
            }
            else if($count == 2) {
                $direct_sales_amount = DB::table('quin_daily_sales')->select(
                    DB::raw('COALESCE(SUM(personal_sales + referral_sales),0) as direct_sales'),
                    DB::raw("(COALESCE(SUM(personal_sales + referral_sales),0) * ".DB::getTablePrefix()."quin_roles_meta.direct_bm_bonus) as direct_commissions")
                )
                ->join('quin_users_meta','quin_daily_sales.users_key','=','quin_users_meta.users_key')
                ->join('quin_roles_meta',
                    function($join) use ($role) {
                        $join->where("quin_roles_meta.id", '=', $role);
                    }
                )
                ->joinSub($roles_data, 'roles_data', function ($join) {
                    $join->on('quin_users_meta.users_id', '=', 'roles_data.users_id');
                })
                ->whereIn('quin_users_meta.status', $this->allowStatus)
                ->where('quin_users_meta.mentor_id','=',$this->referral_code)
                ->where('roles_data.roles','=', $count+1)
                ->where('quin_daily_sales.date','=',date("Y-m-d", strtotime("-1 day", strtotime($date))))
                ->groupBy('direct_bm_bonus');
            }
            else {
                $direct_sales_amount = DB::table('quin_daily_sales')->select(
                    DB::raw('COALESCE(SUM(personal_sales + referral_sales),0) as direct_sales'),
                    DB::raw("(COALESCE(SUM(personal_sales + referral_sales),0) * ".DB::getTablePrefix()."quin_roles_meta.direct_bd_bonus) as direct_commissions")
                )
                ->join('quin_users_meta','quin_daily_sales.users_key','=','quin_users_meta.users_key')
                ->join('quin_roles_meta',
                    function($join) use ($role) {
                        $join->where("quin_roles_meta.id", '=', $role);
                    }
                )
                ->joinSub($roles_data, 'roles_data', function ($join) {
                    $join->on('quin_users_meta.users_id', '=', 'roles_data.users_id');
                })
                ->whereIn('quin_users_meta.status', $this->allowStatus)
                ->where('quin_users_meta.mentor_id','=',$this->referral_code)
                ->where('roles_data.roles','=', $count+1)
                ->where('quin_daily_sales.date','=',date("Y-m-d", strtotime("-1 day", strtotime($date))))
                ->groupBy('direct_bd_bonus');
            }

            $direct_sales_amount = $direct_sales_amount->first();

            if(isset($direct_sales_amount)){
                $direct_sales[$count] = (float) $direct_sales_amount->direct_sales;
                $direct_commissions[$count] = (float) $direct_sales_amount->direct_commissions;
            }
        }


        // $direct_count = array(0,0,0,0);
        // $direct_sales = array(0,0,0,0);
        // $direct_bonus = array(0,0,0,0);
        // $direct_commissions = array(0,0,0,0);


        // return $direct_sales;

        try{
            DailySales::where([
                ['referral_code', '=', $this->referral_code],
                ['date', '=', date("Y-m-d", strtotime("-1 day", strtotime($date)))]
            ])->update([
                'dir_ba_count' => $direct_count[0],
                'dir_ba_sales' => $direct_sales[0],
                'dir_ba_bonus' => $direct_bonus[0],
                'dir_ba_commissions' => $direct_commissions[0],
                'dir_be_count' => $direct_count[1],
                'dir_be_sales' => $direct_sales[1],
                'dir_be_bonus' => $direct_bonus[1],
                'dir_be_commissions' => $direct_commissions[1],
                'dir_bm_count' => $direct_count[2],
                'dir_bm_sales' => $direct_sales[2],
                'dir_bm_bonus' => $direct_bonus[2],
                'dir_bm_commissions' => $direct_commissions[2],
                'dir_bd_count' => $direct_count[3],
                'dir_bd_sales' => $direct_sales[3],
                'dir_bd_bonus' => $direct_bonus[3],
                'dir_bd_commissions' => $direct_commissions[3]
            ]);
            return array('status'=>true);
        }catch(\Illuminate\Database\QueryException $ex){
            return array('status'=>false,'ex'=>$ex);
        }

    }


    public function recordGroupSales($date){
        $date = date_format(date_create($date), "Y-m-d 00:00:00");

        $grp_ids = $this->getGroupCodes($date);

        $grp_count = array(0,0,0);
        $grp_sales = array(0,0,0);


        if(!empty($grp_ids)){
            $subquery = DB::table("quin_roles_history_meta")
            ->select('users_id', DB::raw('max(created_at) as role_date'))
            ->where('created_at', '<', $date)
            ->groupBy('users_id');

            $roles_data = DB::table('quin_roles_history_meta')
            ->select('quin_roles_history_meta.users_id', 'quin_roles_history_meta.roles')
            ->joinSub($subquery,'role_his', function ($join) {
                $join->on('quin_roles_history_meta.users_id', '=', 'role_his.users_id')
                ->on('quin_roles_history_meta.created_at', '=', 'role_his.role_date');
            })
            ->orderBy('quin_roles_history_meta.users_id');


            foreach($grp_count as $i => $value){
                $group_sales = DB::table('quin_daily_sales')->select(
                    DB::raw('count(distinct '.DB::getTablePrefix().'quin_users_meta.referral_code) as group_child_count'),
                    DB::raw('COALESCE(SUM(personal_sales + referral_sales),0) as group_sales')
                )
                ->join('quin_users_meta','quin_daily_sales.users_key','=','quin_users_meta.users_key')
                ->joinSub($roles_data, 'roles_data', function ($join) {
                    $join->on('quin_users_meta.users_id', '=', 'roles_data.users_id');
                })
                ->whereIn('quin_users_meta.status', $this->allowStatus)
                ->whereIn('quin_users_meta.referral_code', $grp_ids)
                ->where('roles_data.roles','=',$i+1)
                ->where('quin_daily_sales.date','=',date("Y-m-d", strtotime("-1 day", strtotime($date))))
                ->first();

                if(isset($group_sales)){
                    $grp_count[$i] = (int) $group_sales->group_child_count;
                    $grp_sales[$i] = (float) $group_sales->group_sales;
                }
            }
        }


        if((int) $this->getRole($date) == 4) {
            array_push($grp_ids, $this->referral_code);

            $group_coms = DB::table('quin_daily_sales')->select(
                DB::raw(DB::getTablePrefix()."quin_options.option_value as personal_vol_bonus"),
                DB::raw("COALESCE(SUM(personal_sales + referral_sales),0) * COALESCE(".DB::getTablePrefix()."quin_options.option_value, 0) as personal_vol_commissions")
            )
            ->leftJoin('quin_options',  function($join) {
                $join->where('quin_options.option_name', '=', 'personal_volume_bonus');
            })
            ->join('quin_users_meta','quin_daily_sales.users_key','=','quin_users_meta.users_key')
            ->whereIn('quin_users_meta.status', $this->allowStatus)
            ->whereIn('quin_users_meta.referral_code', $grp_ids)
            ->where('quin_daily_sales.date','=',date("Y-m-d", strtotime("-1 day", strtotime($date))))
            ->groupBy('quin_options.option_value')
            ->first();
        }
        else {
            $group_coms = (object) array(
                'personal_vol_bonus' => null,
                'personal_vol_commissions' => null
            );
        }


        try{
            DailySales::where([
                ['referral_code', '=', $this->referral_code],
                ['date', '=', date("Y-m-d", strtotime("-1 day", strtotime($date)))]
            ])->update([
                'grp_ba_count' => $grp_count[0],
                'grp_ba_sales' => $grp_sales[0],
                'grp_be_count' => $grp_count[1],
                'grp_be_sales' => $grp_sales[1],
                'grp_bm_count' => $grp_count[2],
                'grp_bm_sales' => $grp_sales[2],
                'personal_vol_bonus' => $group_coms->personal_vol_bonus,
                'personal_vol_commissions' => $group_coms->personal_vol_commissions
            ]);
            return array('status'=>true);
        }catch(\Illuminate\Database\QueryException $ex){
            return array('status'=>false,'ex'=>$ex);
        }
    }



    public function recordFirstLvlBDSales($date){
        $date = date_format(date_create($date), "Y-m-d 00:00:00");
        $first_bd_ids = $this->getBDsCode(1, $date);

        $first_bd_sales = (object) array();
        $first_bd_sales->first_bd_sales = 0;
        $first_bd_sales->first_bd_bonus = 0;
        $first_bd_sales->first_bd_commissions = 0;

        if(!empty($first_bd_ids)){
            $first_bd_sales = DB::table('quin_daily_sales')->select(
                DB::raw('COALESCE(SUM(personal_sales + referral_sales + grp_ba_sales + grp_be_sales + grp_bm_sales),0) as first_bd_sales'),
                DB::raw(DB::getTablePrefix().'quin_options.option_value as first_bd_bonus'),
                DB::raw('COALESCE(SUM(personal_sales + referral_sales + grp_ba_sales + grp_be_sales + grp_bm_sales),0) * COALESCE('.DB::getTablePrefix().'quin_options.option_value, 0) as first_bd_commissions')
            )
            ->leftJoin('quin_options',  function($join) {
                $join->where('quin_options.option_name', '=', 'first_bd_bonus');
            })
            ->join('quin_users_meta','quin_daily_sales.users_key','=','quin_users_meta.users_key')
            ->whereIn('quin_users_meta.status', $this->allowStatus)
            ->whereIn('quin_daily_sales.referral_code', $first_bd_ids)
            ->where('quin_daily_sales.date','=',date("Y-m-d", strtotime("-1 day", strtotime($date))))
            ->groupBy('quin_options.option_value')
            ->first();
        }
        else {
            $bonus = DB::table('quin_options')->select(
                DB::raw(DB::getTablePrefix().'quin_options.option_value as first_bd_bonus')
            )
            ->where('quin_options.option_name', '=', 'first_bd_bonus')
            ->first();

            $first_bd_sales->first_bd_bonus = $bonus->first_bd_bonus;
        }

        try{
            DailySales::where([
                ['referral_code', '=', $this->referral_code],
                ['date', '=', date("Y-m-d", strtotime("-1 day", strtotime($date)))]
            ])->update([
                'first_bd_count' => count($first_bd_ids),
                'first_bd_sales' => (float) $first_bd_sales->first_bd_sales,
                'first_bd_bonus' => (float) $first_bd_sales->first_bd_bonus,
                'first_bd_commissions' => (float) $first_bd_sales->first_bd_commissions,
            ]);
            return array('status'=>true);
        }catch(\Illuminate\Database\QueryException $ex){
            return array('status'=>false,'ex'=>$ex);
        }
    }



    public function recordSecondLvlBDSales($date){
        $date = date_format(date_create($date), "Y-m-d 00:00:00");
        $second_bd_ids = $this->getBDsCode(2, $date);

        $second_bd_sales = (object) array();
        $second_bd_sales->second_bd_sales = 0;
        $second_bd_sales->second_bd_bonus = 0;
        $second_bd_sales->second_bd_commissions = 0;

        if(!empty($second_bd_ids)){
            $second_bd_sales = DB::table('quin_daily_sales')->select(
                DB::raw('COALESCE(SUM(personal_sales + referral_sales + grp_ba_sales + grp_be_sales + grp_bm_sales),0) as second_bd_sales'),
                DB::raw(DB::getTablePrefix().'quin_options.option_value as second_bd_bonus'),
                DB::raw('COALESCE(SUM(personal_sales + referral_sales + grp_ba_sales + grp_be_sales + grp_bm_sales),0) * COALESCE('.DB::getTablePrefix().'quin_options.option_value, 0) as second_bd_commissions')
            )
            ->leftJoin('quin_options',  function($join) {
                $join->where('quin_options.option_name', '=', 'second_bd_bonus');
            })
            ->join('quin_users_meta','quin_daily_sales.users_key','=','quin_users_meta.users_key')
            ->whereIn('quin_users_meta.status', $this->allowStatus)
            ->whereIn('quin_daily_sales.referral_code', $second_bd_ids)
            ->where('quin_daily_sales.date','=',date("Y-m-d", strtotime("-1 day", strtotime($date))))
            ->groupBy('quin_options.option_value')
            ->first();
        }
        else {
            $bonus = DB::table('quin_options')->select(
                DB::raw(DB::getTablePrefix().'quin_options.option_value as second_bd_bonus')
            )
            ->where('quin_options.option_name', '=', 'second_bd_bonus')
            ->first();

            $second_bd_sales->second_bd_bonus = $bonus->second_bd_bonus;
        }

        try{
            DailySales::where([
                ['referral_code', '=', $this->referral_code],
                ['date', '=', date("Y-m-d", strtotime("-1 day", strtotime($date)))]
            ])->update([
                'second_bd_count' => count($second_bd_ids),
                'second_bd_sales' => (float) $second_bd_sales->second_bd_sales,
                'second_bd_bonus' => (float) $second_bd_sales->second_bd_bonus,
                'second_bd_commissions' => (float) $second_bd_sales->second_bd_commissions,
            ]);
            return array('status'=>true);
        }catch(\Illuminate\Database\QueryException $ex){
            return array('status'=>false,'ex'=>$ex);
        }
    }


    // TODO
    public function calLvlUpgrade($date) {
        if((int) date('j', strtotime($date)) == 1) {
            $startDate = date_create($date);
            date_sub($startDate, date_interval_create_from_date_string("1 months"));
            $startDate = date_format($startDate, "Y-m-01 00:00:00");

            $endDate = date_format(date_create($date), "Y-m-d 00:00:00");
        }
        else {
            $startDate = date_format(date_create($date), "Y-m-01 00:00:00");
            $endDate = date_format(date_create($date), "Y-m-d 00:00:00");
        }

        $kpi = false;
        $role = (int) $this->getRole($endDate);

        $upgrade_requirement = DB::table('quin_daily_sales')->select(
            'quin_daily_sales.referral_code',
            DB::raw('max(date) as date'),
            DB::raw("coalesce(sum(".DB::getTablePrefix()."quin_daily_sales.personal_sales + ".DB::getTablePrefix()."quin_daily_sales.referral_sales), 0) as personal_sales"),
            DB::raw("coalesce(sum(".DB::getTablePrefix()."quin_daily_sales.grp_ba_sales + ".DB::getTablePrefix()."quin_daily_sales.grp_be_sales + ".DB::getTablePrefix()."quin_daily_sales.grp_bm_sales), 0) as group_sales"),
            DB::raw(DB::getTablePrefix()."quin_roles_meta.id as role"),
            DB::raw(DB::getTablePrefix()."quin_roles_meta.personal_sales_requirement as personal_sales_requirement"),
            DB::raw(DB::getTablePrefix()."quin_roles_meta.group_sales_requirement as group_sales_requirement"),
            DB::raw(DB::getTablePrefix()."quin_roles_meta.group_sales_requirement_2 as group_sales_requirement_2")
        )
        ->join('quin_roles_meta', function($join) use ($role) {
            $join->where('quin_roles_meta.id', '=', $role);
        })
        ->where('quin_daily_sales.referral_code', '=', $this->referral_code)
        ->where('quin_daily_sales.date', '>=', $startDate)
        ->where('quin_daily_sales.date', '<', $endDate)
        ->groupBy('quin_daily_sales.referral_code', 'quin_roles_meta.id', 'quin_roles_meta.personal_sales_requirement', 'quin_roles_meta.group_sales_requirement', 'quin_roles_meta.group_sales_requirement_2')
        ->first();

        if($upgrade_requirement->personal_sales_requirement == null && $upgrade_requirement->group_sales_requirement == null) {
            return array('status'=>false,'ex'=>'Invalid user for level upgrade.');
        }
        else if($upgrade_requirement->personal_sales_requirement == null && $upgrade_requirement->group_sales_requirement != null) {
            if($upgrade_requirement->group_sales >= $upgrade_requirement->group_sales_requirement && $upgrade_requirement->personal_sales >= $upgrade_requirement->group_sales_requirement_2) {
                $kpi = true;
            }
        }
        else if($upgrade_requirement->personal_sales_requirement != null && $upgrade_requirement->group_sales_requirement == null) {
            if($upgrade_requirement->personal_sales >= $upgrade_requirement->personal_sales_requirement) {
                $kpi = true;
            }
        }
        else {
            if(($upgrade_requirement->personal_sales >= $upgrade_requirement->personal_sales_requirement)
                || ($upgrade_requirement->group_sales >= $upgrade_requirement->group_sales_requirement && $upgrade_requirement->personal_sales >= $upgrade_requirement->group_sales_requirement_2)
            ) {
                $kpi = true;
            }
        }

        return array('status'=>$kpi,'details'=>$upgrade_requirement);
    }

    public function recordLvlUpgrade($date) {
        $result = $this->calLvlUpgrade($date);

        $endDate = date_format(date_create($date), "Y-m-d 00:00:00");

        if($result['status'] == true) {
            $new_role_history = new QuinRolesHistory;
            $new_role_history->users_id =  $this->users_id;
            $new_role_history->roles = ((int) $result['details']->role) + 1;
            $new_role_history->created_at = $endDate;
            $new_role_history->updated_at = formatDateTimeZone(Carbon::now(), 1);

            try{
                $new_role_history->save();

                return array('status'=>true);

            }catch(\Illuminate\Database\QueryException $ex){
                return array('status'=>false,'ex'=>$ex);
            }
        }
        else {
            return array('status'=>"NO HIT");
        }
    }




    ////////////////////////////////////////////////////////////////////
    // Record Monthly Commissions

    public function recordPersonalCommissions($date){

        $personal_sales_arr = $this->calPersonalCommissions($date);
        $referral_sales_arr = $this->calReferralCommissions($date);

        $order_item_ids = array_merge($personal_sales_arr['unclaimed_order_item_ids'], $referral_sales_arr['unclaimed_order_item_ids']);


        $monthly_coms = new MonthlyCommissions;
        $monthly_coms->id = date_format(date_create($date), "ymd").$this->referral_code.Str::random(18);
        $monthly_coms->date = date_format(date_create($date), "Y-m-d");
        $monthly_coms->users_key = $this->users_key;
        $monthly_coms->referral_code = $this->referral_code;
        $monthly_coms->role = (int) $personal_sales_arr['personal_sales']->role;

        $monthly_coms->personal_sales = (float) $personal_sales_arr['personal_sales']->personal_sales;
        $monthly_coms->personal_bonus = (float) $personal_sales_arr['personal_sales']->personal_bonus;
        $monthly_coms->personal_commissions = (float) $personal_sales_arr['personal_sales']->personal_commissions;

        $monthly_coms->referral_sales = (float) $referral_sales_arr['referral_sales']->referral_sales;
        $monthly_coms->referral_bonus = (float) $referral_sales_arr['referral_sales']->referral_bonus;
        $monthly_coms->referral_commissions = (float) $referral_sales_arr['referral_sales']->referral_commissions;

        try{
            $exist_data = MonthlyCommissions::where([
                ['referral_code', $this->referral_code],
                ['date', date_format(date_create($date), "Y-m-d")]
            ])
            ->first();

            if($exist_data == null) {
                if($monthly_coms->save()) {

                    $update_result = DB::table('quin_order_item_meta')
                    ->whereIn('order_item_id', $order_item_ids)
                    ->update(['monthly_coms_id' => $monthly_coms->id]);

                    return array('status'=>true);
                }
            }
            else {
                MonthlyCommissions::where([
                    ['referral_code', '=', $this->referral_code],
                    ['date', '=', date_format(date_create($date), "Y-m-d")]
                ])->update([
                    'role' => (int) $personal_sales_arr['personal_sales']->role,
                    'personal_sales' => (float) $personal_sales_arr['personal_sales']->personal_sales,
                    'personal_bonus' => (float) $personal_sales_arr['personal_sales']->personal_bonus,
                    'personal_commissions' => (float) $personal_sales_arr['personal_sales']->personal_commissions,
                    'referral_sales' => (float) $referral_sales_arr['referral_sales']->referral_sales,
                    'referral_bonus' => (float) $referral_sales_arr['referral_sales']->referral_bonus,
                    'referral_commissions' => (float) $referral_sales_arr['referral_sales']->referral_commissions
                ]);


                $update_result = DB::table('quin_order_item_meta')
                ->whereIn('order_item_id', $order_item_ids)
                ->update(['monthly_coms_id' => $exist_data->id]);

                return array('status'=>true);
            }
        }catch(\Illuminate\Database\QueryException $ex){
            if($ex->getCode() == '23000'){
                return array('status'=>false,'ex'=>$ex);
            }
        }
    }

    public function calPersonalCommissions($date) {
        $date = date("Y-m-d 00:00:00", strtotime("+1 day", strtotime($date)));

        $role = (int) $this->getRole($date);
        $unclaimed_order_item_ids = array();


        // get unclaimed order items
        $order_item_ids = DB::table('quin_order_item_meta')->select(
            'quin_order_item_meta.order_item_id'
        )
        ->distinct()
        ->join('wc_order_stats', function($join) {
            $join->on('quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
                ->whereIn('wc_order_stats.status',array("wc-processing","wc-shipping","wc-delivered","wc-completed"));
        })
        ->join('quin_users_meta', function($join) {
            $join->on('quin_order_item_meta.customer_id', '=', 'quin_users_meta.users_id')
                ->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                ->where('quin_users_meta.users_key', '=', $this->users_key);
        })
        ->where(function($query) {
            $query->whereNull('quin_order_item_meta.monthly_coms_id');
        })
        ->where('quin_order_item_meta.date_created', '<', $date)
        ->get();

        foreach($order_item_ids as $ids) {
            array_push($unclaimed_order_item_ids, (int) $ids->order_item_id);
        }



        //get personal sales
        $personal_sales = DB::table('quin_users_meta')->select(
            'quin_users_meta.referral_code',
            DB::raw(DB::getTablePrefix().'quin_roles_meta.id as role'),
            DB::raw("(coalesce(".DB::getTablePrefix()."quin_roles_meta.referral_amount, 0) - coalesce(".DB::getTablePrefix()."quin_options.option_value, 0)) as personal_bonus"),
            DB::raw('coalesce(sum('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 0) as personal_sales'),
            DB::raw("(coalesce(".DB::getTablePrefix()."quin_roles_meta.referral_amount, 0) - coalesce(".DB::getTablePrefix()."quin_options.option_value, 0)) * coalesce(sum(".DB::getTablePrefix()."quin_order_item_meta.product_subtotal), 0) as personal_commissions")
        )
        ->leftJoin('quin_roles_meta', function($join) use ($role) {
            $join->where('quin_roles_meta.id', '=', $role);
        })
        ->leftJoin('quin_options',  function($join) {
            $join->where('quin_options.option_name', '=', 'partner_discount');
        })
        ->leftJoin('quin_order_item_meta', function($join) use ($unclaimed_order_item_ids) {
            $join->on('quin_users_meta.users_id', '=', 'quin_order_item_meta.customer_id')
                ->whereIn('quin_order_item_meta.order_item_id', $unclaimed_order_item_ids);
        })
        ->where('quin_users_meta.users_key', $this->users_key)
        ->groupBy('quin_users_meta.referral_code', 'quin_roles_meta.id', 'quin_roles_meta.referral_amount', 'quin_options.option_value')
        ->first();

        return array(
            'unclaimed_order_item_ids' => $unclaimed_order_item_ids,
            'personal_sales' => $personal_sales
        );
    }

    public function calReferralCommissions($date) {
        $date = date("Y-m-d 00:00:00", strtotime("+1 day", strtotime($date)));

        $role = (int) $this->getRole($date);
        $unclaimed_order_item_ids = array();


        // get unclaimed order items
        $order_item_ids = DB::table('quin_order_item_meta')->select(
            'quin_order_item_meta.order_item_id'
        )
        ->distinct()
        ->join('wc_order_stats', function($join) {
            $join->on('quin_order_item_meta.order_id', '=', 'wc_order_stats.order_id')
                ->whereIn('wc_order_stats.status',array("wc-processing","wc-shipping","wc-delivered","wc-completed"));
        })
        ->join('quin_users_meta', function($join) {
            $join->on('quin_order_item_meta.sold_by', '=', 'quin_users_meta.referral_code')
                ->on('quin_order_item_meta.date_created', '>', 'quin_users_meta.partner_joined_at')
                ->where('quin_users_meta.users_key', '=', $this->users_key);
        })
        ->where(function($query) {
            $query->whereNull('quin_order_item_meta.monthly_coms_id');
        })
        ->where('quin_order_item_meta.date_created', '<', $date)
        ->get();

        foreach($order_item_ids as $ids) {
            array_push($unclaimed_order_item_ids, (int) $ids->order_item_id);
        }



        //get referral sales
        $referral_sales = DB::table('quin_users_meta')->select(
            'quin_users_meta.referral_code',
            DB::raw(DB::getTablePrefix().'quin_roles_meta.id as role'),
            DB::raw("coalesce(".DB::getTablePrefix()."quin_roles_meta.referral_amount, 0) as referral_bonus"),
            DB::raw('coalesce(sum('.DB::getTablePrefix().'quin_order_item_meta.product_subtotal), 0) as referral_sales'),
            DB::raw("coalesce(".DB::getTablePrefix()."quin_roles_meta.referral_amount, 0) * coalesce(sum(".DB::getTablePrefix()."quin_order_item_meta.product_subtotal), 0) as referral_commissions")
        )
        ->leftJoin('quin_roles_meta', function($join) use ($role) {
            $join->where('quin_roles_meta.id', '=', $role);
        })
        ->leftJoin('quin_order_item_meta', function($join) use ($unclaimed_order_item_ids) {
            $join->on('quin_users_meta.referral_code', '=', 'quin_order_item_meta.sold_by')
                ->whereIn('quin_order_item_meta.order_item_id', $unclaimed_order_item_ids);
        })
        ->where('quin_users_meta.users_key', $this->users_key)
        ->groupBy('quin_users_meta.referral_code', 'quin_roles_meta.id', 'quin_roles_meta.referral_amount')
        ->first();

        return array(
            'unclaimed_order_item_ids' => $unclaimed_order_item_ids,
            'referral_sales' => $referral_sales
        );
    }


    // save direct child sales & commissions into db
    public function recordDirectCommissions($date){
        $date = date("Y-m-d 00:00:00", strtotime("+1 day", strtotime($date)));

        $role = (int) $this->getRole($date);

        $direct_count = array(0,0,0,0);
        $direct_sales = array(0,0,0,0);
        $direct_bonus = array(0,0,0,0);
        $direct_commissions = array(0,0,0,0);


        // get direct bonus
        $bonus_data = DB::table('quin_roles_meta')
        ->select('direct_ba_bonus', 'direct_be_bonus', 'direct_bm_bonus', 'direct_bd_bonus')
        ->where('id', $role)
        ->first();

        $direct_bonus[0] = (float) $bonus_data->direct_ba_bonus;
        $direct_bonus[1] = (float) $bonus_data->direct_be_bonus;
        $direct_bonus[2] = (float) $bonus_data->direct_bm_bonus;
        $direct_bonus[3] = (float) $bonus_data->direct_bd_bonus;


        //get user current role
        $subquery = DB::table("quin_roles_history_meta")
        ->select('users_id', DB::raw('max(created_at) as role_date'))
        ->where('created_at', '<', $date)
        ->groupBy('users_id');

        $roles_data = DB::table('quin_roles_history_meta')
        ->select('quin_roles_history_meta.users_id', 'quin_roles_history_meta.roles')
        ->joinSub($subquery,'role_his', function ($join) {
            $join->on('quin_roles_history_meta.users_id', '=', 'role_his.users_id')
            ->on('quin_roles_history_meta.created_at', '=', 'role_his.role_date');
        })
        ->orderBy('quin_roles_history_meta.users_id');

        //calculate data
        foreach($direct_count as $count => $value){
            $direct_count[$count] = DB::table('quin_monthly_commissions')
            ->select('quin_users_meta.referral_code','quin_users_meta.mentor_id','roles_data.roles')
            ->join('quin_users_meta','quin_monthly_commissions.users_key','=','quin_users_meta.users_key')
            ->joinSub($roles_data, 'roles_data', function ($join) {
                $join->on('quin_users_meta.users_id', '=', 'roles_data.users_id');
            })
            ->whereIn('quin_users_meta.status', $this->allowStatus)
            ->where('quin_users_meta.mentor_id','=',$this->referral_code)
            ->where('roles_data.roles','=',$count+1)
            ->where('quin_monthly_commissions.date','=',date("Y-m-d", strtotime("-1 day", strtotime($date))))
            ->count();

            if($count == 0) {
                $direct_sales_amount = DB::table('quin_monthly_commissions')->select(
                    DB::raw('COALESCE(SUM(personal_sales + referral_sales),0) as direct_sales'),
                    DB::raw("(COALESCE(SUM(personal_sales + referral_sales),0) * ".DB::getTablePrefix()."quin_roles_meta.direct_ba_bonus) as direct_commissions")
                )
                ->join('quin_users_meta','quin_monthly_commissions.users_key','=','quin_users_meta.users_key')
                ->join('quin_roles_meta',
                        function($join) use ($role) {
                            $join->where("quin_roles_meta.id", '=', $role);
                        }
                    )
                ->joinSub($roles_data, 'roles_data', function ($join) {
                    $join->on('quin_users_meta.users_id', '=', 'roles_data.users_id');
                })
                ->whereIn('quin_users_meta.status', $this->allowStatus)
                ->where('quin_users_meta.mentor_id','=',$this->referral_code)
                ->where('roles_data.roles','=', $count+1)
                ->where('quin_monthly_commissions.date','=',date("Y-m-d", strtotime("-1 day", strtotime($date))))
                ->groupBy('direct_ba_bonus');
            }
            else if($count == 1) {
                $direct_sales_amount = DB::table('quin_monthly_commissions')->select(
                    DB::raw('COALESCE(SUM(personal_sales + referral_sales),0) as direct_sales'),
                    DB::raw("(COALESCE(SUM(personal_sales + referral_sales),0) * ".DB::getTablePrefix()."quin_roles_meta.direct_be_bonus) as direct_commissions")
                )
                ->join('quin_users_meta','quin_monthly_commissions.users_key','=','quin_users_meta.users_key')
                ->join('quin_roles_meta',
                        function($join) use ($role) {
                            $join->where("quin_roles_meta.id", '=', $role);
                        }
                    )
                ->joinSub($roles_data, 'roles_data', function ($join) {
                    $join->on('quin_users_meta.users_id', '=', 'roles_data.users_id');
                })
                ->whereIn('quin_users_meta.status', $this->allowStatus)
                ->where('quin_users_meta.mentor_id','=',$this->referral_code)
                ->where('roles_data.roles','=', $count+1)
                ->where('quin_monthly_commissions.date','=',date("Y-m-d", strtotime("-1 day", strtotime($date))))
                ->groupBy('direct_be_bonus');
            }
            else if($count == 2) {
                $direct_sales_amount = DB::table('quin_monthly_commissions')->select(
                    DB::raw('COALESCE(SUM(personal_sales + referral_sales),0) as direct_sales'),
                    DB::raw("(COALESCE(SUM(personal_sales + referral_sales),0) * ".DB::getTablePrefix()."quin_roles_meta.direct_bm_bonus) as direct_commissions")
                )
                ->join('quin_users_meta','quin_monthly_commissions.users_key','=','quin_users_meta.users_key')
                ->join('quin_roles_meta',
                        function($join) use ($role) {
                            $join->where("quin_roles_meta.id", '=', $role);
                        }
                    )
                ->joinSub($roles_data, 'roles_data', function ($join) {
                    $join->on('quin_users_meta.users_id', '=', 'roles_data.users_id');
                })
                ->whereIn('quin_users_meta.status', $this->allowStatus)
                ->where('quin_users_meta.mentor_id','=',$this->referral_code)
                ->where('roles_data.roles','=', $count+1)
                ->where('quin_monthly_commissions.date','=',date("Y-m-d", strtotime("-1 day", strtotime($date))))
                ->groupBy('direct_bm_bonus');
            }
            else {
                $direct_sales_amount = DB::table('quin_monthly_commissions')->select(
                    DB::raw('COALESCE(SUM(personal_sales + referral_sales),0) as direct_sales'),
                    DB::raw("(COALESCE(SUM(personal_sales + referral_sales),0) * ".DB::getTablePrefix()."quin_roles_meta.direct_bd_bonus) as direct_commissions")
                )
                ->join('quin_users_meta','quin_monthly_commissions.users_key','=','quin_users_meta.users_key')
                ->join('quin_roles_meta',
                        function($join) use ($role) {
                            $join->where("quin_roles_meta.id", '=', $role);
                        }
                    )
                ->joinSub($roles_data, 'roles_data', function ($join) {
                    $join->on('quin_users_meta.users_id', '=', 'roles_data.users_id');
                })
                ->whereIn('quin_users_meta.status', $this->allowStatus)
                ->where('quin_users_meta.mentor_id','=',$this->referral_code)
                ->where('roles_data.roles','=', $count+1)
                ->where('quin_monthly_commissions.date','=',date("Y-m-d", strtotime("-1 day", strtotime($date))))
                ->groupBy('direct_bd_bonus');
            }


            $direct_sales_amount = $direct_sales_amount->first();

            if(isset($direct_sales_amount)){
                $direct_sales[$count] = (float) $direct_sales_amount->direct_sales;
                $direct_commissions[$count] = (float) $direct_sales_amount->direct_commissions;
            }
        }

        try{
            MonthlyCommissions::where([
                ['referral_code', '=', $this->referral_code],
                ['date', '=', date("Y-m-d", strtotime("-1 day", strtotime($date)))]
            ])->update([
                'dir_ba_count' => $direct_count[0],
                'dir_ba_sales' => $direct_sales[0],
                'dir_ba_bonus' => $direct_bonus[0],
                'dir_ba_commissions' => $direct_commissions[0],
                'dir_be_count' => $direct_count[1],
                'dir_be_sales' => $direct_sales[1],
                'dir_be_bonus' => $direct_bonus[1],
                'dir_be_commissions' => $direct_commissions[1],
                'dir_bm_count' => $direct_count[2],
                'dir_bm_sales' => $direct_sales[2],
                'dir_bm_bonus' => $direct_bonus[2],
                'dir_bm_commissions' => $direct_commissions[2],
                'dir_bd_count' => $direct_count[3],
                'dir_bd_sales' => $direct_sales[3],
                'dir_bd_bonus' => $direct_bonus[3],
                'dir_bd_commissions' => $direct_commissions[3]
            ]);
            return array('status'=>true);
        }catch(\Illuminate\Database\QueryException $ex){
            return array('status'=>false,'ex'=>$ex);
        }

    }


    public function recordGroupCommissions($date){
        $date = date("Y-m-d 00:00:00", strtotime("+1 day", strtotime($date)));

        $grp_ids = $this->getGroupCodes($date);

        $grp_count = array(0,0,0);
        $grp_sales = array(0,0,0);


        if(!empty($grp_ids)){
            $subquery = DB::table("quin_roles_history_meta")
            ->select('users_id', DB::raw('max(created_at) as role_date'))
            ->where('created_at', '<', $date)
            ->groupBy('users_id');

            $roles_data = DB::table('quin_roles_history_meta')
            ->select('quin_roles_history_meta.users_id', 'quin_roles_history_meta.roles')
            ->joinSub($subquery,'role_his', function ($join) {
                $join->on('quin_roles_history_meta.users_id', '=', 'role_his.users_id')
                ->on('quin_roles_history_meta.created_at', '=', 'role_his.role_date');
            })
            ->orderBy('quin_roles_history_meta.users_id');


            foreach($grp_count as $i => $value){
                $group_sales = DB::table('quin_monthly_commissions')->select(
                    DB::raw('count(distinct '.DB::getTablePrefix().'quin_users_meta.referral_code) as group_child_count'),
                    DB::raw('COALESCE(SUM(personal_sales + referral_sales),0) as group_sales')
                )
                ->join('quin_users_meta','quin_monthly_commissions.users_key','=','quin_users_meta.users_key')
                ->joinSub($roles_data, 'roles_data', function ($join) {
                    $join->on('quin_users_meta.users_id', '=', 'roles_data.users_id');
                })
                ->whereIn('quin_users_meta.status', $this->allowStatus)
                ->whereIn('quin_users_meta.referral_code', $grp_ids)
                ->where('roles_data.roles','=',$i+1)
                ->where('quin_monthly_commissions.date','=',date("Y-m-d", strtotime("-1 day", strtotime($date))))
                ->first();

                if(isset($group_sales)){
                    $grp_count[$i] = (int) $group_sales->group_child_count;
                    $grp_sales[$i] = (float) $group_sales->group_sales;
                }
            }
        }

        if((int) $this->getRole($date) == 4) {
            array_push($grp_ids, $this->referral_code);

            $group_coms = DB::table('quin_monthly_commissions')->select(
                DB::raw(DB::getTablePrefix()."quin_options.option_value as personal_vol_bonus"),
                DB::raw("COALESCE(SUM(personal_sales + referral_sales),0) * COALESCE(".DB::getTablePrefix()."quin_options.option_value, 0) as personal_vol_commissions")
            )
            ->leftJoin('quin_options',  function($join) {
                $join->where('quin_options.option_name', '=', 'personal_volume_bonus');
            })
            ->join('quin_users_meta','quin_monthly_commissions.users_key','=','quin_users_meta.users_key')
            ->whereIn('quin_users_meta.status', $this->allowStatus)
            ->whereIn('quin_users_meta.referral_code', $grp_ids)
            ->where('quin_monthly_commissions.date','=',date("Y-m-d", strtotime("-1 day", strtotime($date))))
            ->groupBy('quin_options.option_value')
            ->first();
        }
        else {
            $group_coms = (object) array(
                'personal_vol_bonus' => null,
                'personal_vol_commissions' => null
            );
        }

        try{
            MonthlyCommissions::where([
                ['referral_code', '=', $this->referral_code],
                ['date', '=', date("Y-m-d", strtotime("-1 day", strtotime($date)))]
            ])->update([
                'grp_ba_count' => $grp_count[0],
                'grp_ba_sales' => $grp_sales[0],
                'grp_be_count' => $grp_count[1],
                'grp_be_sales' => $grp_sales[1],
                'grp_bm_count' => $grp_count[2],
                'grp_bm_sales' => $grp_sales[2],
                'personal_vol_bonus' => $group_coms->personal_vol_bonus,
                'personal_vol_commissions' => $group_coms->personal_vol_commissions
            ]);
            return array('status'=>true);
        }catch(\Illuminate\Database\QueryException $ex){
            return array('status'=>false,'ex'=>$ex);
        }
    }



    public function recordFirstLvlBDCommissions($date){
        $date = date("Y-m-d 00:00:00", strtotime("+1 day", strtotime($date)));
        $first_bd_ids = $this->getBDsCode(1, $date);

        $first_bd_sales = (object) array();
        $first_bd_sales->first_bd_sales = 0;
        $first_bd_sales->first_bd_bonus = 0;
        $first_bd_sales->first_bd_commissions = 0;

        if(!empty($first_bd_ids)){
            $first_bd_sales = DB::table('quin_monthly_commissions')->select(
                DB::raw('COALESCE(SUM(personal_sales + referral_sales + grp_ba_sales + grp_be_sales + grp_bm_sales),0) as first_bd_sales'),
                DB::raw(DB::getTablePrefix().'quin_options.option_value as first_bd_bonus'),
                DB::raw('COALESCE(SUM(personal_sales + referral_sales + grp_ba_sales + grp_be_sales + grp_bm_sales),0) * COALESCE('.DB::getTablePrefix().'quin_options.option_value, 0) as first_bd_commissions')
            )
            ->leftJoin('quin_options',  function($join) {
                $join->where('quin_options.option_name', '=', 'first_bd_bonus');
            })
            ->join('quin_users_meta','quin_monthly_commissions.users_key','=','quin_users_meta.users_key')
            ->whereIn('quin_users_meta.status', $this->allowStatus)
            ->whereIn('quin_monthly_commissions.referral_code', $first_bd_ids)
            ->where('quin_monthly_commissions.date','=',date("Y-m-d", strtotime("-1 day", strtotime($date))))
            ->groupBy('quin_options.option_value')
            ->first();
        }
        else {
            $bonus = DB::table('quin_options')->select(
                DB::raw(DB::getTablePrefix().'quin_options.option_value as first_bd_bonus')
            )
            ->where('quin_options.option_name', '=', 'first_bd_bonus')
            ->first();

            $first_bd_sales->first_bd_bonus = $bonus->first_bd_bonus;
        }

        try{
            MonthlyCommissions::where([
                ['referral_code', '=', $this->referral_code],
                ['date', '=', date("Y-m-d", strtotime("-1 day", strtotime($date)))]
            ])->update([
                'first_bd_count' => count($first_bd_ids),
                'first_bd_sales' => (float) $first_bd_sales->first_bd_sales,
                'first_bd_bonus' => (float) $first_bd_sales->first_bd_bonus,
                'first_bd_commissions' => (float) $first_bd_sales->first_bd_commissions,
            ]);
            return array('status'=>true);
        }catch(\Illuminate\Database\QueryException $ex){
            return array('status'=>false,'ex'=>$ex);
        }
    }



    public function recordSecondLvlBDCommissions($date){
        $date = date("Y-m-d 00:00:00", strtotime("+1 day", strtotime($date)));
        $second_bd_ids = $this->getBDsCode(2, $date);

        $second_bd_sales = (object) array();
        $second_bd_sales->second_bd_sales = 0;
        $second_bd_sales->second_bd_bonus = 0;
        $second_bd_sales->second_bd_commissions = 0;

        if(!empty($second_bd_ids)){
            $second_bd_sales = DB::table('quin_monthly_commissions')->select(
                DB::raw('COALESCE(SUM(personal_sales + referral_sales + grp_ba_sales + grp_be_sales + grp_bm_sales),0) as second_bd_sales'),
                DB::raw(DB::getTablePrefix().'quin_options.option_value as second_bd_bonus'),
                DB::raw('COALESCE(SUM(personal_sales + referral_sales + grp_ba_sales + grp_be_sales + grp_bm_sales),0) * COALESCE('.DB::getTablePrefix().'quin_options.option_value, 0) as second_bd_commissions')
            )
            ->leftJoin('quin_options',  function($join) {
                $join->where('quin_options.option_name', '=', 'second_bd_bonus');
            })
            ->join('quin_users_meta','quin_monthly_commissions.users_key','=','quin_users_meta.users_key')
            ->whereIn('quin_users_meta.status', $this->allowStatus)
            ->whereIn('quin_monthly_commissions.referral_code', $second_bd_ids)
            ->where('quin_monthly_commissions.date','=',date("Y-m-d", strtotime("-1 day", strtotime($date))))
            ->groupBy('quin_options.option_value')
            ->first();
        }
        else {
            $bonus = DB::table('quin_options')->select(
                DB::raw(DB::getTablePrefix().'quin_options.option_value as second_bd_bonus')
            )
            ->where('quin_options.option_name', '=', 'second_bd_bonus')
            ->first();

            $second_bd_sales->second_bd_bonus = $bonus->second_bd_bonus;
        }

        try{
            MonthlyCommissions::where([
                ['referral_code', '=', $this->referral_code],
                ['date', '=', date("Y-m-d", strtotime("-1 day", strtotime($date)))]
            ])->update([
                'second_bd_count' => count($second_bd_ids),
                'second_bd_sales' => (float) $second_bd_sales->second_bd_sales,
                'second_bd_bonus' => (float) $second_bd_sales->second_bd_bonus,
                'second_bd_commissions' => (float) $second_bd_sales->second_bd_commissions,
            ]);
            return array('status'=>true);
        }catch(\Illuminate\Database\QueryException $ex){
            return array('status'=>false,'ex'=>$ex);
        }
    }


    // TODO

    public function recordMonthlyPayout($startDate, $endDate) {
        $startDate = date_format(date_create($startDate), "Y-m-d");
        $endDate = date_format(date_create($endDate), "Y-m-d");

        $coms_id_arr = array();
        $coms_role_arr = array();
        $kpis = array();
        $total_rewarded_commissions = 0;

        // get monthly commissions ids for later update
        $coms_id = DB::table('quin_monthly_commissions')->select(
            'id',
            'role'
        )
        ->distinct()
        ->where('quin_monthly_commissions.referral_code', '=', $this->referral_code)
        ->where('quin_monthly_commissions.date', '>=', $startDate)
        ->where('quin_monthly_commissions.date', '<=', $endDate)
        ->get();

        foreach($coms_id as $ids) {
            array_push($coms_id_arr, $ids->id);
            array_push($coms_role_arr, $ids->role);
        }


        $payout = DB::table('quin_monthly_commissions')->select(
            'quin_monthly_commissions.referral_code',
            'quin_monthly_commissions.role',
            'quin_roles_meta.name',
            DB::raw('sum('.DB::getTablePrefix().'quin_monthly_commissions.personal_sales) as personal_sales'),
            'quin_monthly_commissions.personal_bonus',
            DB::raw('sum('.DB::getTablePrefix().'quin_monthly_commissions.personal_commissions) as personal_commissions'),

            DB::raw('sum('.DB::getTablePrefix().'quin_monthly_commissions.referral_sales) as referral_sales'),
            'quin_monthly_commissions.referral_bonus',
            DB::raw('sum('.DB::getTablePrefix().'quin_monthly_commissions.referral_commissions) as referral_commissions'),

            DB::raw('sum('.DB::getTablePrefix().'quin_monthly_commissions.dir_ba_sales) as dir_ba_sales'),
            'quin_monthly_commissions.dir_ba_bonus',
            DB::raw('sum('.DB::getTablePrefix().'quin_monthly_commissions.dir_ba_commissions) as dir_ba_commissions'),

            DB::raw('sum('.DB::getTablePrefix().'quin_monthly_commissions.dir_be_sales) as dir_be_sales'),
            'quin_monthly_commissions.dir_be_bonus',
            DB::raw('sum('.DB::getTablePrefix().'quin_monthly_commissions.dir_be_commissions) as dir_be_commissions'),

            DB::raw('sum('.DB::getTablePrefix().'quin_monthly_commissions.dir_bm_sales) as dir_bm_sales'),
            'quin_monthly_commissions.dir_bm_bonus',
            DB::raw('sum('.DB::getTablePrefix().'quin_monthly_commissions.dir_bm_commissions) as dir_bm_commissions'),

            DB::raw('sum('.DB::getTablePrefix().'quin_monthly_commissions.dir_bd_sales) as dir_bd_sales'),
            'quin_monthly_commissions.dir_bd_bonus',
            DB::raw('sum('.DB::getTablePrefix().'quin_monthly_commissions.dir_bd_commissions) as dir_bd_commissions'),

            DB::raw('(case when '.DB::getTablePrefix().'quin_monthly_commissions.role = 4
                then sum(coalesce('.DB::getTablePrefix().'quin_monthly_commissions.personal_sales, 0) + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.referral_sales, 0)
                    + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.grp_ba_sales, 0) + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.grp_be_sales, 0) + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.grp_bm_sales, 0))
                else NULL
                end) as personal_vol_sales'
            ),
            'quin_monthly_commissions.personal_vol_bonus',
            DB::raw('sum('.DB::getTablePrefix().'quin_monthly_commissions.personal_vol_commissions) as personal_vol_commissions'),

            DB::raw('sum('.DB::getTablePrefix().'quin_monthly_commissions.first_bd_sales) as first_bd_sales'),
            'quin_monthly_commissions.first_bd_bonus',
            DB::raw('sum('.DB::getTablePrefix().'quin_monthly_commissions.first_bd_commissions) as first_bd_commissions'),

            DB::raw('sum('.DB::getTablePrefix().'quin_monthly_commissions.second_bd_sales) as second_bd_sales'),
            'quin_monthly_commissions.second_bd_bonus',
            DB::raw('sum('.DB::getTablePrefix().'quin_monthly_commissions.second_bd_commissions) as second_bd_commissions')
        )
        ->join('quin_roles_meta', 'quin_monthly_commissions.role', '=', 'quin_roles_meta.id')
        ->where('quin_monthly_commissions.referral_code', '=', $this->referral_code)
        ->where('quin_monthly_commissions.date', '>=', $startDate)
        ->where('quin_monthly_commissions.date', '<=', $endDate)
        ->groupBy('quin_monthly_commissions.referral_code', 'quin_monthly_commissions.role', 'quin_roles_meta.name',
            'quin_monthly_commissions.personal_bonus', 'quin_monthly_commissions.referral_bonus',
            'quin_monthly_commissions.dir_ba_bonus', 'quin_monthly_commissions.dir_be_bonus', 'quin_monthly_commissions.dir_bm_bonus', 'quin_monthly_commissions.dir_bd_bonus',
            'quin_monthly_commissions.personal_vol_bonus', 'quin_monthly_commissions.first_bd_bonus', 'quin_monthly_commissions.second_bd_bonus'
        )
        ->get();


        $kpi_coms = DB::table('quin_monthly_commissions')->select(
            'quin_monthly_commissions.referral_code',
            DB::raw(DB::getTablePrefix().'toption1.option_value as monthly_kpi'),
            DB::raw('(case when sum('.DB::getTablePrefix().'quin_monthly_commissions.personal_sales + '.DB::getTablePrefix().'quin_monthly_commissions.referral_sales) >= '.DB::getTablePrefix().'toption1.option_value
                then 1
                else 0
                end) monthly_kpi_hit'
            ),
            DB::raw('(case when sum('.DB::getTablePrefix().'quin_monthly_commissions.personal_sales + '.DB::getTablePrefix().'quin_monthly_commissions.referral_sales) >= '.DB::getTablePrefix().'toption1.option_value
                then sum(coalesce('.DB::getTablePrefix().'quin_monthly_commissions.personal_commissions, 0) + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.referral_commissions, 0)
                    + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.dir_ba_commissions, 0) + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.dir_be_commissions, 0)
                    + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.dir_bm_commissions, 0) + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.dir_bd_commissions, 0))
                else 0
                end) per_dir_commissions'
            )
        )
        ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption1'), function($join) {
            $join->where('toption1.option_name', '=', 'monthly_kpi');
        })
        ->where('quin_monthly_commissions.referral_code', '=', $this->referral_code)
        ->where('quin_monthly_commissions.date', '>=', $startDate)
        ->where('quin_monthly_commissions.date', '<=', $endDate)
        ->groupBy('quin_monthly_commissions.referral_code', 'toption1.option_value')
        ->first();

        $kpis['monthly_basic_kpi'] = $kpi_coms;
        $total_rewarded_commissions += (float) $kpi_coms->per_dir_commissions;


        if(in_array(4, $coms_role_arr)) {

            $kpi_coms_bd = DB::table('quin_monthly_commissions')->select(
                'quin_monthly_commissions.referral_code',
                DB::raw(DB::getTablePrefix().'toption2.option_value as personal_vol_kpi'),
                DB::raw('(case when sum('.DB::getTablePrefix().'quin_monthly_commissions.personal_sales + '.DB::getTablePrefix().'quin_monthly_commissions.referral_sales) >= '.DB::getTablePrefix().'toption2.option_value
                    then 1
                    else 0
                    end) personal_vol_kpi_hit'
                ),
                DB::raw('(case when sum('.DB::getTablePrefix().'quin_monthly_commissions.personal_sales + '.DB::getTablePrefix().'quin_monthly_commissions.referral_sales) >= '.DB::getTablePrefix().'toption2.option_value
                    then sum('.DB::getTablePrefix().'quin_monthly_commissions.personal_vol_commissions)
                    else 0
                    end) personal_vol_commissions'
                ),


                DB::raw(DB::getTablePrefix().'toption3.option_value as first_bd_kpi'),
                DB::raw('(case when (sum('.DB::getTablePrefix().'quin_monthly_commissions.personal_sales + '.DB::getTablePrefix().'quin_monthly_commissions.referral_sales) >= '.DB::getTablePrefix().'toption2.option_value)
                    and (sum(coalesce('.DB::getTablePrefix().'quin_monthly_commissions.personal_sales, 0) + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.referral_sales, 0)
                        + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.grp_ba_sales, 0) + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.grp_be_sales, 0) + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.grp_bm_sales, 0)) >= '.DB::getTablePrefix().'toption3.option_value)
                    then 1
                    else 0
                    end) first_bd_kpi_hit'
                ),
                DB::raw('(case when (sum('.DB::getTablePrefix().'quin_monthly_commissions.personal_sales + '.DB::getTablePrefix().'quin_monthly_commissions.referral_sales) >= '.DB::getTablePrefix().'toption2.option_value)
                    and (sum(coalesce('.DB::getTablePrefix().'quin_monthly_commissions.personal_sales, 0) + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.referral_sales, 0)
                        + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.grp_ba_sales, 0) + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.grp_be_sales, 0) + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.grp_bm_sales, 0)) >= '.DB::getTablePrefix().'toption3.option_value)
                    then sum(coalesce(first_bd_commissions, 0))
                    else 0
                    end) first_bd_commissions'
                ),

                DB::raw(DB::getTablePrefix().'toption4.option_value as second_bd_kpi'),
                DB::raw('(case when (sum('.DB::getTablePrefix().'quin_monthly_commissions.personal_sales + '.DB::getTablePrefix().'quin_monthly_commissions.referral_sales) >= '.DB::getTablePrefix().'toption2.option_value)
                    and (sum(coalesce('.DB::getTablePrefix().'quin_monthly_commissions.personal_sales, 0) + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.referral_sales, 0)
                        + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.grp_ba_sales, 0) + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.grp_be_sales, 0) + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.grp_bm_sales, 0)) >= '.DB::getTablePrefix().'toption4.option_value)
                    then 1
                    else 0
                    end) second_bd_kpi_hit'
                ),
                DB::raw('(case when (sum('.DB::getTablePrefix().'quin_monthly_commissions.personal_sales + '.DB::getTablePrefix().'quin_monthly_commissions.referral_sales) >= '.DB::getTablePrefix().'toption2.option_value)
                    and (sum(coalesce('.DB::getTablePrefix().'quin_monthly_commissions.personal_sales, 0) + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.referral_sales, 0)
                        + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.grp_ba_sales, 0) + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.grp_be_sales, 0) + coalesce('.DB::getTablePrefix().'quin_monthly_commissions.grp_bm_sales, 0)) >= '.DB::getTablePrefix().'toption4.option_value)
                    then sum(coalesce(second_bd_commissions, 0))
                    else 0
                    end) second_bd_commissions'
                )
            )
            ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption2'), function($join) {
                $join->where('toption2.option_name', '=', 'vol_personal_req');
            })
            ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption3'), function($join) {
                $join->where('toption3.option_name', '=', 'vol_group_req_first');
            })
            ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption4'), function($join) {
                $join->where('toption4.option_name', '=', 'vol_group_req_second');
            })
            ->where('quin_monthly_commissions.referral_code', '=', $this->referral_code)
            ->where('quin_monthly_commissions.date', '>=', $startDate)
            ->where('quin_monthly_commissions.date', '<=', $endDate)
            ->where('quin_monthly_commissions.role', '=', 4)
            ->groupBy('quin_monthly_commissions.referral_code', 'toption2.option_value', 'toption3.option_value', 'toption4.option_value')
            ->first();

            $kpis['bd_kpi'] = $kpi_coms_bd;

            $total_rewarded_commissions += (float) $kpi_coms_bd->personal_vol_commissions + (float) $kpi_coms_bd->first_bd_commissions + (float) $kpi_coms_bd->second_bd_commissions;
        }


        $acc_details = array(
            "bank_name" => $this->bank_name,
            "beneficiary" => $this->bank_account_name,
            "beneficial_account" => $this->bank_account_no
        );

        $payout_details = array(
            "start_date" => $startDate,
            "end_date" => $endDate,
            "payout" => $payout,
            "kpi" => $kpis
        );

        $newMonthlyPayout = new MonthlyPayout;
        $newMonthlyPayout->payout_id = 'po_' . Str::random(29);
        $newMonthlyPayout->date = $startDate;
        $newMonthlyPayout->partner_id = $this->referral_code;
        $newMonthlyPayout->amount = $total_rewarded_commissions;
        $newMonthlyPayout->account_details = json_encode($acc_details);
        $newMonthlyPayout->payout_details = json_encode($payout_details);
        $newMonthlyPayout->status = 1;
        $newMonthlyPayout->created_at = formatDateTimeZone(Carbon::now(), 1);

        try{
            if($newMonthlyPayout->save()) {

                $update_result = DB::table('quin_monthly_commissions')
                    ->whereIn('id', $coms_id_arr)
                    ->update(['payout_id' => $newMonthlyPayout->payout_id]);

                return array('status'=>true);
            }
        }catch(\Illuminate\Database\QueryException $ex){

            return array('status'=>false,'ex'=>$ex);
        }
    }


    ///////////////////////////////////////////////////////


    public function getGroupCountAndSalesOverview($date) {
        $startDate = date_format(date_create($date), "Y-m-01");
        $endDate = date_format(date_create($date), "Y-m-d");


            $result = DB::table('quin_daily_sales')
            ->select(
                'date',
                'referral_code',
                DB::raw('COALESCE(grp_ba_count, 0) as grp_ba_count'),
                DB::raw('COALESCE(grp_be_count, 0) as grp_be_count'),
                DB::raw('COALESCE(grp_bm_count, 0) as grp_bm_count')
            )
            ->where('referral_code', '=', $this->referral_code)
            ->where('date', '<', $endDate)
            ->orderByDesc('date')
            ->first();

            if($result == null) {
                $result = (object) array(
                    'date' => null,
                    'referral_code' => $this->referral_code,
                    'grp_ba_count' => 0,
                    'grp_be_count' => 0,
                    'grp_bm_count' => 0
                );
            }

            $result_2 = DB::table('quin_daily_sales')
            ->select(
                'referral_code',
                DB::raw('COALESCE(SUM(grp_ba_sales), 0) as grp_ba_sales'),
                DB::raw('COALESCE(SUM(grp_be_sales), 0) as grp_be_sales'),
                DB::raw('COALESCE(SUM(grp_bm_sales), 0) as grp_bm_sales'),
                DB::raw('SUM(COALESCE(personal_sales, 0) + COALESCE(referral_sales, 0) + COALESCE(grp_ba_sales, 0) + COALESCE(grp_be_sales, 0) + COALESCE(grp_bm_sales, 0)) as total_grp_sales')
            )
            ->where('referral_code', '=', $this->referral_code)
            ->where('date', '>=', $startDate)
            ->where('date', '<', $endDate)
            ->groupBy('referral_code')
            ->first();

            if($result_2 == null) {
                $result_2 = (object) array(
                    'referral_code' => $this->referral_code,
                    'grp_ba_sales' => 0,
                    'grp_be_sales' => 0,
                    'grp_bm_sales' => 0
                );
            }


            return array("count" => $result, "sales" => $result_2);



    }


    public function getGroupCountAndSalesDetails($date) {
        $startDate = date_format(date_create($date), "Y-m-01");
        $endDate = date_format(date_create($date), "Y-m-d");

        if($this->getRole($endDate) == 4) {
            $result = DB::table("quin_daily_sales")
            ->select(
                'referral_code',
                DB::raw('COALESCE(SUM(personal_sales + referral_sales), 0) as total_sales')
            )
            ->whereIn("referral_code", $this->getGroupCodes($endDate))
            ->where('date', '>=', $startDate)
            ->where('date', '<', $endDate)
            ->groupBy('referral_code')
            ->orderByDesc('total_sales')
            ->get();

            return array("group" => $this->getGroup(null, $endDate), "sales" => $result);
        }

        return false;
    }


    public function getFirstBDCountAndSalesOverview($date) {
        $startDate = date_format(date_create($date), "Y-m-01");
        $endDate = date_format(date_create($date), "Y-m-d");

        if($this->getRole($endDate) == 4) {
            $result = DB::table('quin_daily_sales')
            ->select(
                'date',
                'referral_code',
                DB::raw('COALESCE(first_bd_count, 0) as first_level_bd_count')
            )
            ->where('referral_code', '=', $this->referral_code)
            ->where('date', '<', $endDate)
            ->orderByDesc('date')
            ->first();

            if($result == null) {
                $result = (object) array(
                    'date' => null,
                    'referral_code' => $this->referral_code,
                    'first_level_bd_count' => 0
                );
            }

            $result_2 = DB::table('quin_daily_sales')
            ->select(
                'referral_code',
                DB::raw('COALESCE(SUM(first_bd_sales), 0) as first_level_bd_sales')
            )
            ->where('referral_code', '=', $this->referral_code)
            ->where('date', '>=', $startDate)
            ->where('date', '<', $endDate)
            ->groupBy('referral_code')
            ->first();

            if($result_2 == null) {
                $result_2 = (object) array(
                    'referral_code' => $this->referral_code,
                    'first_level_bd_sales' => 0
                );
            }

            return array("count" => $result, "sales" => $result_2);
        }

        return false;
    }


    public function getFirstBDCountAndSalesDetails($date) {
        $startDate = date_format(date_create($date), "Y-m-01");
        $endDate = date_format(date_create($date), "Y-m-d");


        if($this->getRole($endDate) == 4) {

            $subquery = DB::table("quin_daily_sales")
            ->select('referral_code', DB::raw('max(date) as record_date'))
            ->whereIn("referral_code", $this->getBDsCode(1, $endDate))
            ->where('date', '<', $endDate)
            ->groupBy('referral_code');


            $result = DB::table('quin_daily_sales')
            ->select(
                'quin_daily_sales.referral_code',
                DB::raw('COALESCE(grp_ba_count, 0) as grp_ba_count'),
                DB::raw('COALESCE(grp_be_count, 0) as grp_be_count'),
                DB::raw('COALESCE(grp_bm_count, 0) as grp_bm_count')
            )
            ->joinSub($subquery, 'record_data', function ($join) {
                $join->on('quin_daily_sales.referral_code', '=', 'record_data.referral_code')
                    ->on('quin_daily_sales.date', '=', 'record_data.record_date');
            })
            ->orderBy('quin_daily_sales.referral_code')
            ->get();



            $result_2 = DB::table("quin_daily_sales")
            ->select(
                'referral_code',
                DB::raw('COALESCE(SUM(personal_sales + referral_sales), 0) as personal_sales'),
                DB::raw('SUM(COALESCE(grp_ba_sales, 0) + COALESCE(grp_be_sales, 0) + COALESCE(grp_bm_sales, 0)) as total_grp_sales')
            )
            ->whereIn("referral_code", $this->getBDsCode(1, $endDate))
            ->where('date', '>=', $startDate)
            ->where('date', '<', $endDate)
            ->groupBy('referral_code')
            ->orderBy('referral_code')
            ->get();

            return array("bds" => $this->getBDs(1, $endDate), "count" => $result, "sales" => $result_2);
        }

        return false;
    }



    public function getSecondBDCountAndSalesOverview($date) {
        $startDate = date_format(date_create($date), "Y-m-01");
        $endDate = date_format(date_create($date), "Y-m-d");

        if($this->getRole($endDate) == 4) {
            $result = DB::table('quin_daily_sales')
            ->select(
                'date',
                'referral_code',
                DB::raw('COALESCE(second_bd_count, 0) as second_level_bd_count')
            )
            ->where('referral_code', '=', $this->referral_code)
            ->where('date', '<', $endDate)
            ->orderByDesc('date')
            ->first();

            if($result == null) {
                $result = (object) array(
                    'date' => null,
                    'referral_code' => $this->referral_code,
                    'second_level_bd_count' => 0
                );
            }

            $result_2 = DB::table('quin_daily_sales')
            ->select(
                'referral_code',
                DB::raw('COALESCE(SUM(second_bd_sales), 0) as second_level_bd_sales')
            )
            ->where('referral_code', '=', $this->referral_code)
            ->where('date', '>=', $startDate)
            ->where('date', '<', $endDate)
            ->groupBy('referral_code')
            ->first();

            if($result_2 == null) {
                $result_2 = (object) array(
                    'referral_code' => $this->referral_code,
                    'second_level_bd_sales' => 0
                );
            }

            return array("count" => $result, "sales" => $result_2);
        }

        return false;
    }


    public function getSecondBDCountAndSalesDetails($date) {
        $startDate = date_format(date_create($date), "Y-m-01");
        $endDate = date_format(date_create($date), "Y-m-d");


        if($this->getRole($endDate) == 4) {

            $subquery = DB::table("quin_daily_sales")
            ->select('referral_code', DB::raw('max(date) as record_date'))
            ->whereIn("referral_code", $this->getBDsCode(2, $endDate))
            ->where('date', '<', $endDate)
            ->groupBy('referral_code');


            $result = DB::table('quin_daily_sales')
            ->select(
                'quin_daily_sales.referral_code',
                DB::raw('COALESCE(grp_ba_count, 0) as grp_ba_count'),
                DB::raw('COALESCE(grp_be_count, 0) as grp_be_count'),
                DB::raw('COALESCE(grp_bm_count, 0) as grp_bm_count')
            )
            ->joinSub($subquery, 'record_data', function ($join) {
                $join->on('quin_daily_sales.referral_code', '=', 'record_data.referral_code')
                    ->on('quin_daily_sales.date', '=', 'record_data.record_date');
            })
            ->orderBy('quin_daily_sales.referral_code')
            ->get();



            $result_2 = DB::table("quin_daily_sales")
            ->select(
                'referral_code',
                DB::raw('COALESCE(SUM(personal_sales + referral_sales), 0) as personal_sales'),
                DB::raw('SUM(COALESCE(grp_ba_sales, 0) + COALESCE(grp_be_sales, 0) + COALESCE(grp_bm_sales, 0)) as total_grp_sales')
            )
            ->whereIn("referral_code", $this->getBDsCode(2, $endDate))
            ->where('date', '>=', $startDate)
            ->where('date', '<', $endDate)
            ->groupBy('referral_code')
            ->orderBy('referral_code')
            ->get();

            return array("bds" => $this->getBDs(2, $endDate), "count" => $result, "sales" => $result_2);
        }

        return false;
    }




    public function getTripIncentives($date) {
        $startDate = date_format(date_create($date), "Y-01-01");
        $endDate = date_format(date_create($date), "Y-m-d");

        if($this->getRole($endDate) == 4) {

            $subQuery = DB::table('quin_daily_sales')
            ->select(
                'referral_code',
                DB::raw('max(date) as end_date'),
                DB::raw('sum('.DB::getTablePrefix().'quin_daily_sales.personal_sales + '.DB::getTablePrefix().'quin_daily_sales.referral_sales) as personal_sales'),

                DB::raw('sum(coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_ba_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_be_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_bm_sales, 0)) as group_sales'),

                DB::raw('sum(coalesce(first_bd_sales, 0) + coalesce(second_bd_sales, 0)) as all_bd_sales'),

                DB::raw(DB::getTablePrefix().'toption1.option_value as trip_personal_one_person'),
                DB::raw('(case when sum('.DB::getTablePrefix().'quin_daily_sales.personal_sales + '.DB::getTablePrefix().'quin_daily_sales.referral_sales) >= '.DB::getTablePrefix().'toption1.option_value
                    then 1
                    else 0
                    end) trip_personal_one_hit'
                ),

                DB::raw(DB::getTablePrefix().'toption2.option_value as trip_personal_two_person'),
                DB::raw('(case when sum('.DB::getTablePrefix().'quin_daily_sales.personal_sales + '.DB::getTablePrefix().'quin_daily_sales.referral_sales) >= '.DB::getTablePrefix().'toption2.option_value
                    then 1
                    else 0
                    end) trip_personal_two_hit'
                ),

                DB::raw(DB::getTablePrefix().'toption3.option_value as trip_group_one_person'),
                DB::raw('(case when sum(coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_ba_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_be_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_bm_sales, 0)) >= '.DB::getTablePrefix().'toption3.option_value
                    then 1
                    else 0
                    end) trip_group_one_hit'
                ),

                DB::raw(DB::getTablePrefix().'toption4.option_value as trip_group_two_person'),
                DB::raw('(case when sum(coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_ba_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_be_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_bm_sales, 0)) >= '.DB::getTablePrefix().'toption4.option_value
                    then 1
                    else 0
                    end) trip_group_two_hit'
                ),

                DB::raw(DB::getTablePrefix().'toption5.option_value as trip_bd_one_person'),
                DB::raw('(case when sum(coalesce(first_bd_sales, 0) + coalesce(second_bd_sales, 0))  >= '.DB::getTablePrefix().'toption5.option_value
                    then 1
                    else 0
                    end) trip_bd_one_hit'
                ),

                DB::raw(DB::getTablePrefix().'toption6.option_value as trip_bd_two_person'),
                DB::raw('(case when sum(coalesce(first_bd_sales, 0) + coalesce(second_bd_sales, 0))  >= '.DB::getTablePrefix().'toption6.option_value
                    then 1
                    else 0
                    end) trip_bd_two_hit'
                )
            )
            ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption1'), function($join) {
                $join->where('toption1.option_name', '=', 'trip_personal_one_person');
            })
            ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption2'), function($join) {
                $join->where('toption2.option_name', '=', 'trip_personal_two_person');
            })
            ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption3'), function($join) {
                $join->where('toption3.option_name', '=', 'trip_group_one_person');
            })
            ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption4'), function($join) {
                $join->where('toption4.option_name', '=', 'trip_group_two_person');
            })
            ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption5'), function($join) {
                $join->where('toption5.option_name', '=', 'trip_bd_one_person');
            })
            ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption6'), function($join) {
                $join->where('toption6.option_name', '=', 'trip_bd_two_person');
            })
            ->where('referral_code', '=', $this->referral_code)
            ->where('quin_daily_sales.role', '=', 4)
            ->where('date', '>=', $startDate)
            ->where('date', '<', $endDate)
            ->groupBy('referral_code', 'toption1.option_value', 'toption2.option_value', 'toption3.option_value', 'toption4.option_value', 'toption5.option_value', 'toption6.option_value');


            $result = DB::table(DB::raw('('.$subQuery->toSql().') as '.DB::getTablePrefix().'t1'))->select(
                't1.*',

                DB::raw('(case when '.DB::getTablePrefix().'t1.trip_personal_one_hit = 1 and '.DB::getTablePrefix().'t1.trip_group_one_hit = 1 and '.DB::getTablePrefix().'t1.trip_bd_one_hit = 1 then 1
                    when '.DB::getTablePrefix().'t1.trip_personal_two_hit = 1 and '.DB::getTablePrefix().'t1.trip_group_two_hit = 1 and '.DB::getTablePrefix().'t1.trip_bd_two_hit = 1 then 2
                    else 0
                    end) as num_of_pax'
                )
            )
            ->mergeBindings($subQuery)
            ->first();

            return array("start_date" => $startDate, "result" => $result);
        }

        return false;
    }


    public function getCrown($date) {
        $startDate = date_format(date_create($date), "Y-01-01");
        $endDate = date_format(date_create($date), "Y-m-d");

        if($this->getRole($endDate) == 4) {
            $result = DB::table('quin_daily_sales')
            ->select(
                'referral_code',
                DB::raw('max(date) as end_date'),
                DB::raw('sum('.DB::getTablePrefix().'quin_daily_sales.personal_sales + '.DB::getTablePrefix().'quin_daily_sales.referral_sales) as personal_sales'),

                DB::raw('sum(coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_ba_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_be_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_bm_sales, 0)) as group_sales'),

                DB::raw('sum(coalesce(first_bd_sales, 0) + coalesce(second_bd_sales, 0)) as all_bd_sales'),

                DB::raw(DB::getTablePrefix().'toption1.option_value as crown_personal'),
                DB::raw('(case when sum('.DB::getTablePrefix().'quin_daily_sales.personal_sales + '.DB::getTablePrefix().'quin_daily_sales.referral_sales) >= '.DB::getTablePrefix().'toption1.option_value
                    then 1
                    else 0
                    end) crown_personal_hit'
                ),

                DB::raw(DB::getTablePrefix().'toption2.option_value as crown_group'),
                DB::raw('(case when sum(coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_ba_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_be_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_bm_sales, 0)) >= '.DB::getTablePrefix().'toption2.option_value
                    then 1
                    else 0
                    end) crown_group_hit'
                ),

                DB::raw(DB::getTablePrefix().'toption3.option_value as crown_bd'),
                DB::raw('(case when sum(coalesce(first_bd_sales, 0) + coalesce(second_bd_sales, 0))  >= '.DB::getTablePrefix().'toption3.option_value
                    then 1
                    else 0
                    end) crown_bd_hit'
                )

            )
            ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption1'), function($join) {
                $join->where('toption1.option_name', '=', 'crown_personal');
            })
            ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption2'), function($join) {
                $join->where('toption2.option_name', '=', 'crown_group');
            })
            ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption3'), function($join) {
                $join->where('toption3.option_name', '=', 'crown_bd');
            })
            ->where('referral_code', '=', $this->referral_code)
            ->where('quin_daily_sales.role', '=', 4)
            ->where('date', '>=', $startDate)
            ->where('date', '<', $endDate)
            ->groupBy('referral_code', 'toption1.option_value', 'toption2.option_value', 'toption3.option_value')
            ->first();

            return array("start_date" => $startDate, "result" => $result);
        }

        return false;
    }


    public function getVolumeBonus($date) {
        $startDate = date_format(date_create($date), "Y-m-01");
        $endDate = date_format(date_create($date), "Y-m-d");

        if($this->getRole($endDate) == 4) {
            $kpi_coms_bd = DB::table('quin_daily_sales')->select(
                'quin_daily_sales.referral_code',
                DB::raw('max(date) as date'),
                DB::raw('sum('.DB::getTablePrefix().'quin_daily_sales.personal_sales + '.DB::getTablePrefix().'quin_daily_sales.referral_sales) as personal_sales'),
                DB::raw('sum(coalesce('.DB::getTablePrefix().'quin_daily_sales.personal_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.referral_sales, 0)
                + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_ba_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_be_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_bm_sales, 0)) as personal_volume_sales'),

                DB::raw(DB::getTablePrefix().'toption5.option_value as personal_volume_bonus'),
                DB::raw(DB::getTablePrefix().'toption6.option_value as first_bd_bonus'),
                DB::raw(DB::getTablePrefix().'toption7.option_value as second_bd_bonus'),

                DB::raw(DB::getTablePrefix().'toption2.option_value as personal_vol_kpi'),
                DB::raw('(case when sum('.DB::getTablePrefix().'quin_daily_sales.personal_sales + '.DB::getTablePrefix().'quin_daily_sales.referral_sales) >= '.DB::getTablePrefix().'toption2.option_value
                    then 1
                    else 0
                    end) personal_vol_kpi_hit'
                ),


                DB::raw(DB::getTablePrefix().'toption3.option_value as first_bd_kpi'),
                DB::raw('(case when (sum('.DB::getTablePrefix().'quin_daily_sales.personal_sales + '.DB::getTablePrefix().'quin_daily_sales.referral_sales) >= '.DB::getTablePrefix().'toption2.option_value)
                    and (sum(coalesce('.DB::getTablePrefix().'quin_daily_sales.personal_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.referral_sales, 0)
                        + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_ba_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_be_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_bm_sales, 0)) >= '.DB::getTablePrefix().'toption3.option_value)
                    then 1
                    else 0
                    end) first_bd_kpi_hit'
                ),

                DB::raw(DB::getTablePrefix().'toption4.option_value as second_bd_kpi'),
                DB::raw('(case when (sum('.DB::getTablePrefix().'quin_daily_sales.personal_sales + '.DB::getTablePrefix().'quin_daily_sales.referral_sales) >= '.DB::getTablePrefix().'toption2.option_value)
                    and (sum(coalesce('.DB::getTablePrefix().'quin_daily_sales.personal_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.referral_sales, 0)
                        + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_ba_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_be_sales, 0) + coalesce('.DB::getTablePrefix().'quin_daily_sales.grp_bm_sales, 0)) >= '.DB::getTablePrefix().'toption4.option_value)
                    then 1
                    else 0
                    end) second_bd_kpi_hit'
                ),
            )
            ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption2'), function($join) {
                $join->where('toption2.option_name', '=', 'vol_personal_req');
            })
            ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption3'), function($join) {
                $join->where('toption3.option_name', '=', 'vol_group_req_first');
            })
            ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption4'), function($join) {
                $join->where('toption4.option_name', '=', 'vol_group_req_second');
            })
            ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption5'), function($join) {
                $join->where('toption5.option_name', '=', 'personal_volume_bonus');
            })
            ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption6'), function($join) {
                $join->where('toption6.option_name', '=', 'first_bd_bonus');
            })
            ->join(DB::raw(DB::getTablePrefix().'quin_options as '.DB::getTablePrefix().'toption7'), function($join) {
                $join->where('toption7.option_name', '=', 'second_bd_bonus');
            })
            ->where('quin_daily_sales.referral_code', '=', $this->referral_code)
            ->where('quin_daily_sales.date', '>=', $startDate)
            ->where('quin_daily_sales.date', '<', $endDate)
            ->where('quin_daily_sales.role', '=', 4)
            ->groupBy('quin_daily_sales.referral_code', 'toption2.option_value', 'toption3.option_value', 'toption4.option_value', 'toption5.option_value', 'toption6.option_value', 'toption7.option_value')
            ->first();

            return array("result" => $kpi_coms_bd);
        }

        return false;
    }
}
