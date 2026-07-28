<?php

namespace App\Http\Controllers;

use App\Services\TurnstileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TurnstileController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (! $this->isEnabled()) {
            return redirect('/');
        }

        $this->ensureConfigured();

        return view('auth.human-check', [
            'siteKey' => config('services.turnstile.site_key'),
            'action' => config('services.turnstile.action'),
        ]);
    }

    public function verify(Request $request, TurnstileService $turnstile): RedirectResponse
    {
        if (! $this->isEnabled()) {
            return redirect('/');
        }

        $this->ensureConfigured();

        $validated = $request->validate([
            'cf-turnstile-response' => ['required', 'string', 'max:2048'],
        ], [
            'cf-turnstile-response.required' => 'Please complete the human verification.',
        ]);

        if (! $turnstile->verify($validated['cf-turnstile-response'], $request->ip())) {
            return back()
                ->withInput()
                ->withErrors(['turnstile' => 'Human verification failed or expired. Please try again.']);
        }

        $request->session()->put(
            (string) config('services.turnstile.session_key'),
            now()->timestamp,
        );

        $destination = $request->session()->pull('turnstile.intended', '/');

        return redirect()->to(is_string($destination) ? $destination : '/');
    }

    private function ensureConfigured(): void
    {
        if (
            blank(config('services.turnstile.site_key'))
            || blank(config('services.turnstile.secret_key'))
        ) {
            abort(503, 'Human verification is not configured.');
        }
    }

    private function isEnabled(): bool
    {
        return (bool) config('services.turnstile.enabled')
            && (bool) config('demo.enabled')
            && config('demo.runtime') === 'server';
    }
}
