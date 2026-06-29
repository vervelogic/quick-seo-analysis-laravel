<?php

namespace App\Http\Controllers;

use App\Models\LegacyAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController
{
    public function redirect(): RedirectResponse
    {
        if (! $this->configured()) {
            return redirect()
                ->route('login')
                ->withErrors(['google' => 'Google login is not configured yet.']);
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $this->configured()) {
            return redirect()
                ->route('login')
                ->withErrors(['google' => 'Google login is not configured yet.']);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()
                ->route('login')
                ->withErrors(['google' => 'Google sign-in could not be completed. Please try again.']);
        }

        $email = strtolower(trim((string) $googleUser->getEmail()));
        $googleId = trim((string) $googleUser->getId());
        $verified = (bool) data_get($googleUser->user, 'verified_email', data_get($googleUser->user, 'email_verified', false));

        if ($email === '' || $googleId === '' || ! $verified) {
            return redirect()
                ->route('login')
                ->withErrors(['google' => 'A verified Google email is required to continue.']);
        }

        $providerOwner = User::query()->where('google_id', $googleId)->first();
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($providerOwner && $user && $providerOwner->id !== $user->id) {
            return redirect()
                ->route('login')
                ->withErrors(['google' => 'This Google account is already linked to a different QSA user.']);
        }

        $user ??= $providerOwner;

        if ($user) {
            $this->syncGoogleIdentity($user, $googleUser->getName(), $email, $googleId, $googleUser->getAvatar());

            return $this->loginAndRedirect($request, $user, 'Welcome back. Your Google account is now connected.');
        }

        $legacyAccount = LegacyAccount::query()
            ->where('status', LegacyAccount::STATUS_PENDING_CLAIM)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->latest('last_activity_at')
            ->first();

        $user = User::create([
            'name' => $googleUser->getName() ?: Str::before($email, '@'),
            'email' => $email,
            'password' => Str::random(64),
            'role' => 'client',
            'company_role' => User::COMPANY_ROLE_OWNER,
            'google_id' => $googleId,
            'auth_provider' => 'google',
            'avatar_url' => $googleUser->getAvatar(),
            'email_verified_at' => now(),
            'legacy_login_provider' => 'google',
            'invite_required' => false,
        ]);

        if ($legacyAccount) {
            return $this->loginAndRedirect(
                $request,
                $user,
                'Welcome back. We found your previous Quick SEO Analysis account and it is ready to claim.'
            );
        }

        return $this->loginAndRedirect(
            $request,
            $user,
            'Your account is ready. Complete your company setup to start using QSA.'
        );
    }

    private function configured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function syncGoogleIdentity(User $user, ?string $name, string $email, string $googleId, ?string $avatar): void
    {
        $user->forceFill([
            'name' => $user->name ?: ($name ?: Str::before($email, '@')),
            'email' => $email,
            'google_id' => $googleId,
            'auth_provider' => 'google',
            'avatar_url' => $avatar ?: $user->avatar_url,
            'email_verified_at' => $user->email_verified_at ?: now(),
            'legacy_login_provider' => 'google',
            'invite_required' => false,
            'last_active_at' => now(),
        ])->save();
    }

    private function loginAndRedirect(Request $request, User $user, string $status): RedirectResponse
    {
        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard.index'))->with('status', $status);
    }
}
