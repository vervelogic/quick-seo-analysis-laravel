<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('qsa:about', function () {
    $this->info('Quick SEO Analysis v1');
})->purpose('Show Quick SEO Analysis project information');
