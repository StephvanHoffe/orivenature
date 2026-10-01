<?php

use Illuminate\Support\Facades\Schedule;

// Op de hosting: cronjob elke minuut met "php artisan schedule:run"
Schedule::command('orive:verlaten-winkelwagens')->everyFifteenMinutes()->withoutOverlapping();
