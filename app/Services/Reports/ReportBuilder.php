<?php

namespace App\Services\Reports;

use App\Models\Scan;

class ReportBuilder
{
    public function buildPayload(Scan $scan): array
    {
        $scan->loadMissing('result', 'company');

        return [
            'scan' => $scan,
            'result' => $scan->result,
            'company' => $scan->company,
        ];
    }
}
