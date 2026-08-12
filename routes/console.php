<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    //
})->purpose('Display an inspiring quote');

// Bản ghi failed_jobs được giữ 7 ngày để còn điều tra rồi mới dọn.
// Cần cron gọi `php artisan schedule:run` mỗi phút (xem README, mục Queue Worker).
Schedule::command('queue:prune-failed', ['--hours' => 168])->daily();
