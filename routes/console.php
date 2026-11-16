<?php

use Illuminate\Support\Facades\Schedule;

// Marks scheduled appointments from previous days as no-shows.
Schedule::command('appointments:mark-no-shows')->dailyAt('23:30');
