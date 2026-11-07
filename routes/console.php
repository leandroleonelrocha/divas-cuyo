<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Anonymous deduplication tokens only; daily aggregate counts are retained.
\Illuminate\Support\Facades\Schedule::call(function () {
    \Illuminate\Support\Facades\DB::table('profile_stat_visitors')->where('counted_at', '<', now()->subDay())->delete();
})->daily()->name('prune-profile-stat-visitors');
