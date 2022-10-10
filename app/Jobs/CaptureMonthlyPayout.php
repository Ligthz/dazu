<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\QuinUser;
use App\Jobs\CalculateMonthlyPayout;

class CaptureMonthlyPayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The quin user instance.
     *
     * @var \App\Models\QuinUser
     */
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
    public function __construct($startDate, $endDate)
    {
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
        $validStatus = [21, 23];
        $quinUsers = QuinUser::where([
            ['users_id', '!=', 1],
            ['users_id', '!=', 2]
        ])->whereIn('status', $validStatus)->get();

        foreach($quinUsers as $user) {
            CalculateMonthlyPayout::dispatch($user, $this->startDate, $this->endDate);
        }
    }
}
