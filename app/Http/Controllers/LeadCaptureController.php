<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Models\Scan;
use App\Services\Leads\LeadCaptureService;
use Illuminate\Http\RedirectResponse;

class LeadCaptureController
{
    public function __invoke(StoreLeadRequest $request, LeadCaptureService $leads): RedirectResponse
    {
        $scan = Scan::query()->where('uuid', $request->validated('scan_uuid'))->firstOrFail();

        $leads->capture($scan, $request->safe()->except('scan_uuid'));

        return redirect()
            ->route('report.show', ['scan' => $scan->uuid])
            ->with('status', 'Thanks. Your details were saved for the report follow-up.');
    }
}
