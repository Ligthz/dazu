<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\QuinUser;
use App\Jobs\CalculateIndividualMonthlyCommissions;

class CaptureMonthlyCommissions implements ShouldQueue
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

        $quinUsers = QuinUser::where([
            ['users_id', '!=', 1],
            ['users_id', '!=', 2]
        ])->whereIn('status', $validStatus)->get();

        foreach($quinUsers as $user) {
            CalculateIndividualMonthlyCommissions::dispatch($user, $this->date);
        }
    }
}
