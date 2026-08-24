<?php

namespace App\Http\Middleware;

use App\Support\BotGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockUnwantedBots
{
    public function __construct(
        private readonly BotGuard $bots,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('emails.open') || $request->is('up')) {
            return $next($request);
        }

        if ($this->bots->shouldBlockUserAgent($request->userAgent())) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}
