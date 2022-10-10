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
use Carbon\Carbon;

class CalculateMonthlyPayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The quin user instance.
     *
     * @var \App\Models\QuinUser
     */
    protected $quinUser;
    protected $startDate;
    protected $endDate;
    public $tries = 1;
    public $maxExceptions = 1;
    public $timeout = 30;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(QuinUser $quinUser, $startDate, $endDate)
    {
        $this->quinUser = $quinUser;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $response = $this->quinUser->recordMonthlyPayout($this->startDate, $this->endDate);
        if($response['status']==false){
            $this->fail($response['ex']);
        }

    }
}
