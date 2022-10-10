<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\QuinUser;
use App\Models\DailySales;

class CalculateDirectChildren implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

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
        $response = $this->quinUser->recordDirectSales($this->date);
        if($response['status']==false){
            $this->fail($response['ex']);
        }
    }
}
