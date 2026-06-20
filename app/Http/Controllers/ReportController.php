<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use Illuminate\Contracts\View\View;

class ReportController
{
    public function __invoke(Scan $scan): View
    {
        $scan->load(['result', 'leads']);

        return view('reports.show', [
            'scan' => $scan,
            'result' => $scan->result,
        ]);
    }
}
