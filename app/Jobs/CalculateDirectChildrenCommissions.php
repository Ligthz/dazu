<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\QuinUser;

class CalculateDirectChildrenCommissions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $quinUser;
    protected $date;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(QuinUser $quinUser, $date)
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
        $response = $this->quinUser->recordDirectCommissions($this->date);
        if($response['status']==false){
            $this->fail($response['ex']);
        }
    }
}
