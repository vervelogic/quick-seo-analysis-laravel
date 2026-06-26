<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKeywordFocusAuditRequest;
use App\Models\Scan;
use App\Services\Scanner\SeoScanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class KeywordFocusAuditController
{
    public function create(): View
    {
        return view('keyword-focus.create');
    }

    public function store(StoreKeywordFocusAuditRequest $request, SeoScanner $scanner): RedirectResponse
    {
        $keywords = $request->validated('target_keywords');

        $scan = Scan::query()->create([
            'company_id' => $request->user()?->company_id,
            'url' => $request->input('original_url', $request->validated('url')),
            'normalized_url' => $request->input('normalized_url'),
            'scan_mode' => 'keyword_focus',
            'target_keywords' => $keywords,
            'status' => 'pending',
        ]);

        try {
            $scanner->scan($scan, allowHttpFallback: ! $request->boolean('scan_input_had_scheme'));
        } catch (\Throwable $exception) {
            Log::warning('Keyword focus scan failed.', [
                'scan_id' => $scan->id,
                'url' => $scan->normalized_url,
                'message' => $exception->getMessage(),
            ]);

            $scan->result()->updateOrCreate(
                ['scan_id' => $scan->id],
                [
                    'is_reachable' => false,
                    'uses_https' => strtolower(parse_url($scan->normalized_url, PHP_URL_SCHEME) ?: '') === 'https',
                    'score' => 0,
                    'checks' => [],
                    'recommendations' => [],
                    'keyword_alignment_data' => $this->failedAlignment($keywords),
                    'raw' => [
                        'requested_url' => $scan->url,
                        'scan_target_url' => $scan->normalized_url,
                        'final_url' => $scan->normalized_url,
                        'error' => $exception->getMessage(),
                    ],
                ]
            );

            $scan->update([
                'status' => 'failed',
                'error_message' => 'We could not complete this keyword focus audit. Please check the URL and try again.',
                'completed_at' => now(),
            ]);
        }

        return redirect()->route('report.show', ['scan' => $scan->uuid]);
    }

    private function failedAlignment(array $keywords): array
    {
        $rows = array_map(fn (string $keyword): array => [
            'keyword' => $keyword,
            'alignment_score' => 0,
            'status' => 'Missing',
            'found_in' => [],
            'missing_from' => ['URL', 'Title', 'Meta Description', 'H1', 'H2/H3', 'Body Content', 'FAQ', 'Schema', 'Internal Links'],
            'search_intent_match' => 'Weak',
            'content_support' => 'Weak',
            'suggested_on_page_fix' => 'QSA could not fetch the page. Confirm the URL is reachable and scan again.',
        ], $keywords);

        return [
            'overall_score' => 0,
            'summary' => [
                'total' => count($rows),
                'strongly_supported' => 0,
                'partially_supported' => 0,
                'weak_or_missing' => count($rows),
            ],
            'keywords' => $rows,
            'intent_summary' => [
                'strong' => 0,
                'partial' => 0,
                'weak' => count($rows),
            ],
            'content_gaps' => [
                'body_copy' => $keywords,
                'faqs' => $keywords,
                'headings' => $keywords,
                'internal_links' => $keywords,
            ],
        ];
    }
}
