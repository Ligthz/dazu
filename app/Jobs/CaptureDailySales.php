<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\QuinUser;
use App\Jobs\CalculateIndividualDailySales;
use Illuminate\Support\Facades\DB;

class CaptureDailySales implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The quin user instance.
     *
     * @var \App\Models\QuinUser
     */
    protected $date;
    public $tries = 1;
    public $maxExceptions = 1;
    public $timeout = 30;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($date)
    {
        $this->date = $date;
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
        DB::table('quin_error_log')->insert(array('msg'=>"test: ".json_encode( $quinUsers)));

        foreach($quinUsers as $user) {
            DB::table('quin_error_log')->insert(array('msg'=>"calcuating personal sales for ". $user->id. " " . $this->date));
            CalculateIndividualDailySales::dispatch($user, $this->date);
        }
    }
}
