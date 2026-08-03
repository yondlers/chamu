<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use Illuminate\Http\Request;

class EmailLogController extends Controller
{
    public function index(Request $request)
    {
        $status = trim((string) $request->query('status', ''));
        $type = trim((string) $request->query('type', ''));
        $search = trim((string) $request->query('search', ''));

        $emailLogs = EmailLog::with(['user', 'bursaryApplication.bursary.company'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($type !== '', fn ($query) => $query->where('email_type', $type))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('subject', 'like', '%'.$search.'%')
                        ->orWhere('primary_recipient_email', 'like', '%'.$search.'%')
                        ->orWhere('primary_recipient_name', 'like', '%'.$search.'%')
                        ->orWhere('company_name', 'like', '%'.$search.'%')
                        ->orWhere('bursary_title', 'like', '%'.$search.'%')
                        ->orWhere('applicant_name', 'like', '%'.$search.'%')
                        ->orWhere('applicant_email', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate(40)
            ->withQueryString();

        return view('admin.emails.index', [
            'emailLogs' => $emailLogs,
            'filters' => [
                'status' => $status,
                'type' => $type,
                'search' => $search,
            ],
            'totalEmails' => EmailLog::count(),
            'sentEmails' => EmailLog::where('status', 'sent')->count(),
            'failedEmails' => EmailLog::where('status', 'failed')->count(),
            'sendingEmails' => EmailLog::where('status', 'sending')->count(),
            'emailTypes' => EmailLog::whereNotNull('email_type')
                ->distinct()
                ->orderBy('email_type')
                ->pluck('email_type'),
            'archiveAddress' => config('mail.archive.address'),
        ]);
    }

    public function show(EmailLog $emailLog)
    {
        $emailLog->load(['user', 'bursaryApplication.bursary.company']);

        return view('admin.emails.show', [
            'emailLog' => $emailLog,
            'archiveAddress' => config('mail.archive.address'),
        ]);
    }
}
