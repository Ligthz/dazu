<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\QuinUser;

class CaptureDirectChildrenCommissions implements ShouldQueue
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
        $this->date = date_format(date_create($date), "Y-m-d H:i:s");
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
            CalculateDirectChildrenCommissions::dispatch($user,$this->date);
        }
    }
}
