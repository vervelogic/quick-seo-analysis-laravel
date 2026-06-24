<?php

use App\Console\Commands\ImportLegacyDotnetCommand;
use App\Console\Commands\InspectLegacyDotnetCommand;
use Illuminate\Support\Facades\Artisan;

Artisan::command('qsa:about', function () {
    $this->info('Quick SEO Analysis v1');
})->purpose('Show Quick SEO Analysis project information');

Artisan::resolve(InspectLegacyDotnetCommand::class);
Artisan::resolve(ImportLegacyDotnetCommand::class);
