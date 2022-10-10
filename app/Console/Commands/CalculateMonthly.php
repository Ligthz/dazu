<?php

namespace App\Console\Commands;

use App\Jobs\EndOfDay;
use App\Jobs\EndOfMonth;
use Illuminate\Console\Command;

class CalculateMonthly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculate:monthly {date : Calculate commission of the month}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate Monthly Commission';

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
        if ($this->confirm('Are you sure to calculate commission of '.$date)) {
            $this->info('Calculating comission of '.$date);
        }

        $task = EndOfMonth::dispatch($date);
        return 0;
    }
}
