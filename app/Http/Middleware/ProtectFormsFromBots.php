<?php

namespace App\Http\Middleware;

use App\Support\BotGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectFormsFromBots
{
    public function __construct(
        private readonly BotGuard $bots,
    ) {}

    public function handle(Request $request, Closure $next, string $mode = 'default'): Response
    {
        if (! config('bots.enabled') || ! $request->isMethod('POST')) {
            return $next($request);
        }

        if ($this->bots->honeypotFilled($request) || $this->bots->formStartedAt($request) === null) {
            abort(403, 'Access denied.');
        }

        $minimumSeconds = $mode === 'register'
            ? (int) config('bots.register_minimum_seconds', 2)
            : 0;

        if ($this->bots->submittedTooFast($request, $minimumSeconds)) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}
