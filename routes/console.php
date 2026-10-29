<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('offers:expire')->everyMinute();
