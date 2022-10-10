<?php

namespace App\Console\Commands;

use App\Jobs\EndOfDay as JobsEndOfDay;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class endOfDay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bigwave:end-of-day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate Daily Commission';

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
        $date_str = formatDateTimeZone(Carbon::now(),1);
        $date = date_format(date_create($date_str),'Y-m-d');
        DB::table('quin_error_log')->insert(array('msg'=> "End of date: $date"));
        $task = JobsEndOfDay::dispatch($date);
        return 0;
    }
}
