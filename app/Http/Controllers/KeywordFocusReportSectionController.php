<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use Illuminate\Http\Response;

class KeywordFocusReportSectionController
{
    public function __invoke(Scan $scan): Response
    {
        $scan->loadMissing('result');
        $alignment = $scan->result?->keyword_alignment_data;

        if ($scan->scan_mode !== 'keyword_focus' || ! is_array($alignment)) {
            return response('', 204);
        }

        return response(view('reports.partials.keyword-focus-audit', [
            'scan' => $scan,
            'result' => $scan->result,
            'alignment' => $alignment,
        ])->render());
    }
}
