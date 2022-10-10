<?php

namespace App\Console\Commands;

use App\Jobs\CaptureMonthlyPayout;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CalculatePayout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculate:payout {date : Calculate commission of the month}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate Monthly Payout';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $date = $this->argument('date');

        $this->date = $date;

        $start_date = date_create($this->date);
        date_sub($start_date, date_interval_create_from_date_string("1 months"));
        $this->first_of_month = date_format($start_date, "Y-m-01 00:00:00");

        if ($this->confirm('Are you sure to calculate commission of '.$date)) {
            $this->info('Calculating payout of '.$date);
        }

        try{
            CaptureMonthlyPayout::dispatch($this->first_of_month, date("Y-m-t 23:59:59", strtotime($this->first_of_month)));
        }
        catch(Throwable $e) {
            $result = DB::table('quin_error_log')->insert([
                'msg' => $e
            ]);
        }
        return 0;
    }
}
