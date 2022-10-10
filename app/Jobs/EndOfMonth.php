<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Jobs\CaptureMonthlyCommissions;
use App\Jobs\CaptureDirectChildrenCommissions;
use App\Jobs\CaptureGroupCommissions;
use App\Jobs\CaptureBDCommissions;
use App\Jobs\CaptureMonthlyPayout;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Support\Facades\DB;
use Throwable;

class EndOfMonth implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $date;
    protected $first_of_month;
    protected $end_of_month;
    protected $interval;
    protected $period;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($date = null)
    {
        if($date == null){
            $this->date = formatDateTimeZone(Carbon::now(), 1);

            $start_date = date_create($this->date);
            date_sub($start_date, date_interval_create_from_date_string("1 months"));
            $this->first_of_month = date_format($start_date, "Y-m-01 00:00:00");

            $this->end_of_month = date_format(date_create($this->date), "Y-m-01 00:00:00");
            $this->interval = DateInterval::createFromDateString('1 day');
            $this->period = new DatePeriod(new DateTime($this->first_of_month), $this->interval, new DateTime($this->end_of_month));
        }else{
            $this->date = $date;

            $start_date = date_create($this->date);
            date_sub($start_date, date_interval_create_from_date_string("1 months"));
            $this->first_of_month = date_format($start_date, "Y-m-01 00:00:00");

            $this->end_of_month = date_format(date_create($this->date), "Y-m-01 00:00:00");
            $this->interval = DateInterval::createFromDateString('1 day');
            $this->period = new DatePeriod(new DateTime($this->first_of_month), $this->interval, new DateTime($this->end_of_month));
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->period as $dt) {

            $bus_chain = Bus::chain([
                new CaptureMonthlyCommissions($dt->format("Y-m-d H:i:s")),
                new CaptureDirectChildrenCommissions($dt->format("Y-m-d H:i:s")),
                new CaptureGroupCommissions($dt->format("Y-m-d H:i:s")),
                new CaptureBDCommissions($dt->format("Y-m-d H:i:s"))
            ])->catch(function (Throwable $e) {
                $result = DB::table('quin_error_log')->insert([
                    'msg' => $e
                ]);
            })->dispatch();
        }


        try{
            CaptureMonthlyPayout::dispatch($this->first_of_month, date("Y-m-t 23:59:59", strtotime($this->first_of_month)));
        }
        catch(Throwable $e) {
            $result = DB::table('quin_error_log')->insert([
                'msg' => $e
            ]);
        }
    }
}
