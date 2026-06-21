<?php

namespace App\Services\Leads;

use App\Models\Lead;
use App\Models\Scan;

class LeadCaptureService
{
    public function capture(Scan $scan, array $data): Lead
    {
        return Lead::query()->create([
            'company_id' => $scan->company_id,
            'scan_id' => $scan->id,
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'status' => 'new',
            'source_report_uuid' => $scan->uuid,
            'metadata' => [
                'source' => 'public_report',
                'captured_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
