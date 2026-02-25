<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Revisa actos sin PDF todos los días a las 8 AM
Schedule::command('acts:check-pdf-deadlines')->dailyAt('08:00');

// Envía el informe mensual el 1ro de cada mes a las 7 AM
Schedule::command('acts:monthly-report')->monthlyOn(1, '07:00');
