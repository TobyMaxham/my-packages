<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('packages:scan-all')
    ->dailyAt('07:00')
    ->timezone('Europe/Berlin');
