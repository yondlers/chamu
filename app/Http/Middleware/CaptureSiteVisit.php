<?php

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class CaptureSiteVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldCapture($request)) {
            $userAgent = $request->userAgent();

            SiteVisit::create([
                'user_id' => $request->user()?->id,
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'ip_address' => $request->ip(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'route_name' => $request->route()?->getName(),
                'referrer' => $request->headers->get('referer'),
                'user_agent' => $userAgent,
                'device_type' => $this->deviceType($userAgent),
                'platform' => $this->platform($userAgent),
                'browser' => $this->browser($userAgent),
                'visited_at' => now(),
            ]);
        }

        return $response;
    }

    private function shouldCapture(Request $request): bool
    {
        return in_array($request->method(), ['GET', 'HEAD'], true)
            && ! $request->is('admin*')
            && ! $request->is('up')
            && ! $request->is('favicon.ico')
            && ! $request->is('images/*')
            && ! $request->is('storage/*')
            && Schema::hasTable('site_visits');
    }

    private function deviceType(?string $userAgent): string
    {
        $agent = strtolower($userAgent ?? '');

        if ($agent === '') {
            return 'unknown';
        }

        if (str_contains($agent, 'bot') || str_contains($agent, 'crawler') || str_contains($agent, 'spider')) {
            return 'bot';
        }

        if (str_contains($agent, 'ipad') || str_contains($agent, 'tablet')) {
            return 'tablet';
        }

        if (str_contains($agent, 'mobile') || str_contains($agent, 'iphone') || str_contains($agent, 'android')) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function platform(?string $userAgent): string
    {
        $agent = strtolower($userAgent ?? '');

        return match (true) {
            str_contains($agent, 'iphone') || str_contains($agent, 'ipad') => 'iOS',
            str_contains($agent, 'android') => 'Android',
            str_contains($agent, 'mac os') || str_contains($agent, 'macintosh') => 'macOS',
            str_contains($agent, 'windows') => 'Windows',
            str_contains($agent, 'linux') => 'Linux',
            default => 'Unknown',
        };
    }

    private function browser(?string $userAgent): string
    {
        $agent = strtolower($userAgent ?? '');

        return match (true) {
            str_contains($agent, 'edg/') => 'Edge',
            str_contains($agent, 'opr/') || str_contains($agent, 'opera') => 'Opera',
            str_contains($agent, 'samsungbrowser') => 'Samsung Internet',
            str_contains($agent, 'chrome/') || str_contains($agent, 'crios') => 'Chrome',
            str_contains($agent, 'firefox/') || str_contains($agent, 'fxios') => 'Firefox',
            str_contains($agent, 'safari/') => 'Safari',
            default => 'Unknown',
        };
    }
}
