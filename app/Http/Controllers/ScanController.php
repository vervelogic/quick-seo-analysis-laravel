<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScanRequest;
use App\Models\Scan;
use App\Services\Scanner\SeoScanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class ScanController
{
    public function __invoke(StoreScanRequest $request, SeoScanner $scanner): RedirectResponse
    {
        $scan = Scan::query()->create([
            'url' => $request->validated('url'),
            'normalized_url' => $request->input('normalized_url'),
            'status' => 'pending',
        ]);

        try {
            $scanner->scan($scan);
        } catch (\Throwable $exception) {
            Log::warning('SEO scan failed.', [
                'scan_id' => $scan->id,
                'url' => $scan->normalized_url,
                'message' => $exception->getMessage(),
            ]);

            $scan->update([
                'status' => 'failed',
                'error_message' => 'We could not complete this scan. Please check the URL and try again.',
                'completed_at' => now(),
            ]);
        }

        return redirect()->route('report.show', ['scan' => $scan->uuid]);
    }
}
