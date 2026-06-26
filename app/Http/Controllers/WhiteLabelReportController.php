<?php

namespace App\Http\Controllers;

use App\Models\ReportUsage;
use App\Models\Scan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhiteLabelReportController
{
    public function show(Request $request, Scan $scan): View|RedirectResponse
    {
        try {
            $user = $request->user();
            $company = $user?->company()->with('plan')->first();

            abort_unless($company, 403, 'Your account is not assigned to a company workspace.');
            abort_unless($scan->company_id === $company->id, 403, 'This report does not belong to your company workspace.');
            abort_if($scan->legacy_source, 404, 'Legacy archive reports are not available for white-label download.');

            $scan->load('result');

            ReportUsage::query()->create([
                'company_id' => $company->id,
                'user_id' => $user?->id,
                'scan_id' => $scan->id,
                'action' => 'downloaded',
                'channel' => 'white_label_pdf',
                'credits_used' => 1,
                'metadata' => [
                    'scan_uuid' => $scan->uuid,
                    'scan_mode' => $scan->scan_mode,
                    'white_label_active' => $company->white_label_enabled && $company->featureEnabled('white_label_reports'),
                ],
            ]);

            return view('reports.pdf.white-label', [
                'scan' => $scan,
                'result' => $scan->result,
                'company' => $company,
                'whiteLabelActive' => $company->white_label_enabled && $company->featureEnabled('white_label_reports'),
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('White-label PDF report failed.', [
                'scan_id' => $scan->id,
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'pdf' => 'We could not generate the white-label report right now. Please try again.',
            ]);
        }
    }
}
