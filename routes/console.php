<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('telescope:prune')->daily();

Schedule::command('weather:fetch')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('recommendations:generate')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
