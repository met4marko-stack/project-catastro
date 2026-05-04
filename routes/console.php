<?php

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\ArchivarTramitesVencidos;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ArchivarTramitesVencidos::class)->daily();
