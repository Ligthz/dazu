<?php

namespace App\Jobs;

use App\Models\QuinUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CaptureLevelUpgrade implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $date;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($date)
    {
        $this->date = date_format(date_create($date), "Y-m-d 00:00:00");
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $validStatus = [21, 23];
        $exclude_user = [1,2];

        $subquery = DB::table("quin_roles_history_meta")
        ->select('users_id', DB::raw('max(created_at) as role_date'))
        ->where('created_at', '<', $this->date)
        ->groupBy('users_id');

        $ba_be_bm = DB::table('quin_roles_history_meta')
        ->select('quin_roles_history_meta.users_id', 'quin_roles_history_meta.roles')
        ->joinSub($subquery,'role_his', function ($join) {
            $join->on('quin_roles_history_meta.users_id', '=', 'role_his.users_id')
            ->on('quin_roles_history_meta.created_at', '=', 'role_his.role_date');
        })
        ->where('quin_roles_history_meta.roles','>=',1)
        ->where('quin_roles_history_meta.roles','<=',3)
        ->get();

        foreach($ba_be_bm as $partner) {
            $user = QuinUser::where('users_id',$partner->users_id)->whereNotIn('users_id', $exclude_user)->whereIn('status',$validStatus)->first();
            if($user != null){
                CalculateLevelUpgrade::dispatch($user,$this->date);
            }else{
                DB::table('quin_error_log')->insert(array('msg'=>"Calculate Level Up Error: User id: ".$partner->users_id));
            }
        }
    }
}
