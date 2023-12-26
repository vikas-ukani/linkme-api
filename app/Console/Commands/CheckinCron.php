<?php

namespace App\Console\Commands;

use App\Http\Controllers\API\BookinController;
use Illuminate\Console\Command;

class CheckinCron extends Command
{
    protected $signature = 'checkin:cron';
    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // seding reminder notifications before 24h, 1h and 30mins
        echo 'checking booking reminders';
        $booking = new BookinController();
        $booking->notifyRemindBookings();
        $this->info('Checkin:Cron Command Run successfully!');
    }
}
