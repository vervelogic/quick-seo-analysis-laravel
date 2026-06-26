<?php

namespace App\Http\Controllers;

use App\Models\LegacyAccount;
use App\Services\Legacy\LegacyWorkspaceBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LegacyAccountClaimController
{
    public function __invoke(Request $request, LegacyAccount $legacyAccount, LegacyWorkspaceBuilder $workspaceBuilder): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user, 403);
        abort_unless($legacyAccount->isPendingClaim(), 403);
        abort_unless(strtolower((string) $legacyAccount->email) === strtolower((string) $user->email), 403);

        $result = $workspaceBuilder->claim($legacyAccount, $user);

        return redirect()
            ->route('dashboard.index')
            ->with('status', 'Your previous QSA account has been claimed. '.$result['scans_attached'].' historical scans are now in your workspace.');
    }
}
