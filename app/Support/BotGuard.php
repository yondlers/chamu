<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class BotGuard
{
    public function shouldBlockUserAgent(?string $userAgent): bool
    {
        if (! config('bots.enabled')) {
            return false;
        }

        $agent = trim((string) $userAgent);

        if ($agent === '') {
            return true;
        }

        if ($this->matches($agent, config('bots.blocked_user_agents', []))) {
            return true;
        }

        if ($this->matches($agent, config('bots.allowed_user_agents', []))) {
            return false;
        }

        return (bool) preg_match('/bot|crawler|spider|scraper|slurp/i', $agent);
    }

    public function honeypotFilled(Request $request): bool
    {
        $field = (string) config('bots.honeypot_field', 'hp_field');

        return filled($request->input($field));
    }

    public function submittedTooFast(Request $request, int $minimumSeconds): bool
    {
        if ($minimumSeconds < 1) {
            return false;
        }

        $startedAt = $this->formStartedAt($request);

        if ($startedAt === null) {
            return true;
        }

        return $startedAt > now()->subSeconds($minimumSeconds)->getTimestamp();
    }

    public function formStartedAt(Request $request): ?int
    {
        $field = (string) config('bots.form_started_at_field', 'form_started_at');
        $payload = $request->input($field);

        if (! is_string($payload) || $payload === '') {
            return null;
        }

        try {
            $timestamp = (int) Crypt::decryptString($payload);
        } catch (Throwable) {
            return null;
        }

        return $timestamp > 0 ? $timestamp : null;
    }

    /**
     * @param  array<int, string>  $patterns
     */
    private function matches(string $userAgent, array $patterns): bool
    {
        $agent = strtolower($userAgent);

        foreach ($patterns as $pattern) {
            $needle = strtolower(trim((string) $pattern));

            if ($needle !== '' && str_contains($agent, $needle)) {
                return true;
            }
        }

        return false;
    }
}
