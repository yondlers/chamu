<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EmailOpenController extends Controller
{
    public function __invoke(Request $request, string $trackingId): Response
    {
        if (Schema::hasTable('email_logs')) {
            $emailLog = EmailLog::where('tracking_id', $trackingId)->first();

            if ($emailLog) {
                $now = now();

                $emailLog->forceFill([
                    'open_count' => ((int) $emailLog->open_count) + 1,
                    'first_opened_at' => $emailLog->first_opened_at ?? $now,
                    'last_opened_at' => $now,
                    'last_open_ip_address' => $request->ip(),
                    'last_open_user_agent' => $request->userAgent(),
                ])->save();
            }
        }

        return response(base64_decode('R0lGODlhAQABAPAAAP///wAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw=='), 200, [
            'Content-Type' => 'image/gif',
            'Content-Length' => '43',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => 'Mon, 01 Jan 1990 00:00:00 GMT',
        ]);
    }
}
