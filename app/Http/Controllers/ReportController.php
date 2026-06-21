<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use App\Models\ScanResult;
use Illuminate\Contracts\View\View;

class ReportController
{
    public function __invoke(Scan $scan): View
    {
        $scan->load(['result', 'leads']);

        if (! $scan->result && $scan->status === 'failed') {
            $scan->setRelation('result', new ScanResult([
                'scan_id' => $scan->id,
                'is_reachable' => false,
                'uses_https' => strtolower(parse_url($scan->normalized_url, PHP_URL_SCHEME) ?: '') === 'https',
                'score' => 0,
                'checks' => [],
                'recommendations' => [],
                'raw' => [
                    'requested_url' => $scan->normalized_url,
                    'final_url' => $scan->normalized_url,
                    'error' => $scan->error_message,
                ],
            ]));
        }

        $host = parse_url($scan->normalized_url, PHP_URL_HOST);
        $history = collect();

        if ($host) {
            $history = Scan::query()
                ->with('result')
                ->whereKeyNot($scan->getKey())
                ->latest()
                ->limit(40)
                ->get()
                ->filter(fn (Scan $candidate): bool => parse_url($candidate->normalized_url, PHP_URL_HOST) === $host)
                ->take(8)
                ->values();
        }

        return view('reports.show', [
            'scan' => $scan,
            'result' => $scan->result,
            'history' => $history,
        ]);
    }
}
