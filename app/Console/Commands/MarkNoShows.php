<?php

namespace App\Console\Commands;

use App\Services\AppointmentService;
use Illuminate\Console\Command;

class MarkNoShows extends Command
{
    protected $signature = 'appointments:mark-no-shows';

    protected $description = 'Mark scheduled appointments from previous days as no-show';

    public function handle(AppointmentService $appointments): int
    {
        $count = $appointments->markPastScheduledAsNoShow();
        $this->info("{$count} appointment(s) marked as no-show.");

        return self::SUCCESS;
    }
}
