<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Artisan::command('lineworks:token', function () {
    $lineWorksMessagingService = app(\App\Services\LineWorksMessagingService::class);
    $accessToken = $lineWorksMessagingService->getAccessToken();
    logger()->info('Access Token: ', $accessToken);
    $this->info('Access Token: ' . $accessToken['access_token'] ?? 'error');
});
