<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\QuinUser;
use Illuminate\Support\Facades\DB;

class CaptureDirectChildrenSales implements ShouldQueue
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
        $this->date = date_format(date_create($date), "Y-m-d");
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
        $quinUsers = QuinUser::whereNotIn('users_id', $exclude_user)->whereIn('status', $validStatus)->get();

        foreach($quinUsers as $user) {
            if($user != null){
                CalculateDirectChildren::dispatch($user,$this->date);
            }else{
                DB::table('quin_error_log')->insert(array('msg'=>"Calculate Direct Sales Error: User id: ".$user->users_id));
            }
        }
    }
}
