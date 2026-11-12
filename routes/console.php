<?php

use App\Jobs\SyncAmisContracts;
use App\Jobs\SyncLcisProjects;
use Illuminate\Support\Facades\Schedule;

/*
| Nightly full snapshots of both sources (server time). The jobs run on the queue worker.
*/
Schedule::job(new SyncLcisProjects)->dailyAt('01:00')->name('sync-lcis-projects')->onOneServer();
Schedule::job(new SyncAmisContracts)->dailyAt('01:30')->name('sync-amis-contracts')->onOneServer();
