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

class CalculateFirstBDCommissions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $quinUser;
    protected $date;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($quinUser, $date)
    {
        $this->quinUser = $quinUser;
        $this->date = $date;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $response = $this->quinUser->recordFirstLvlBDCommissions($this->date);
        if($response['status']==false){
            $this->fail($response['ex']);
        }
    }
}
