<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Jobs\CaptureDailySales;
use App\Jobs\CaptureDirectChildrenSales;
use App\Jobs\CaptureGroupSales;
use App\Jobs\CaptureBDSales;
use App\Jobs\CaptureLevelUpgrade;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

use function PHPUnit\Framework\isNull;

class EndOfDay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $date;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($date = null)
    {
        if($date == null){
            $this->date = formatDateTimeZone(Carbon::now(), 1);
        }else{
            $this->date = $date;
        }

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $bus_chain = Bus::chain([
            new CaptureDailySales($this->date),
            new CaptureDirectChildrenSales($this->date),
            new CaptureGroupSales($this->date),
            new CaptureBDSales($this->date),
            new CaptureLevelUpgrade($this->date)
        ])->catch(function (Throwable $e) {
            $result = DB::table('quin_error_log')->insert([
                'msg' => $e
            ]);
        })->dispatch();

        return $bus_chain;
    }
}
