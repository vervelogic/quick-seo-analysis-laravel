<?php

namespace App\Jobs;

use App\Models\Scan;
use App\Services\Scanner\SeoScanner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunSeoScan implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 45;

    public function __construct(public readonly int $scanId)
    {
    }

    public function handle(SeoScanner $scanner): void
    {
        $scan = Scan::query()->findOrFail($this->scanId);

        $scanner->scan($scan);
    }
}
