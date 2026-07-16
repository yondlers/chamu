@extends('layouts.app')

@section('title', $account->name.' - Admin - Chamu')

@section('content')
    @php
        $formatPercent = fn ($value) => $value === null ? 'N/A' : rtrim(rtrim(number_format((float) $value, 1), '0'), '.').'%';
        $latestTermLabel = $latestResult
            ? (($latestResult->grade?->name ?? 'Unknown grade').' - '.($latestResult->term?->name ?? 'Unknown term'))
            : 'No marks yet';
    @endphp

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-5 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <a href="{{ route('admin.accounts.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#01225E] hover:underline">
                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Accounts
                </a>
                <p class="mt-5 text-sm font-bold text-[#01225E]">Super admin account view</p>
                <h1 class="mt-1 text-3xl font-bold">{{ $account->name ?: 'Unnamed account' }}</h1>
                <p class="mt-2 max-w-3xl text-neutral-500">{{ $account->email }} - {{ '@'.$account->username }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($account->is_super_admin)
                    <span class="inline-flex items-center rounded-full bg-[#01225E] px-3 py-1.5 text-xs font-bold text-white">Super admin</span>
                @endif
                <span class="inline-flex items-center rounded-full bg-neutral-100 px-3 py-1.5 text-xs font-bold capitalize text-neutral-700">{{ $account->userType?->name ?? 'Unknown type' }}</span>
            </div>
        </div>

        <section class="grid gap-3 md:grid-cols-4">
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Latest APS</p>
                <p class="mt-2 text-3xl font-bold">{{ $latestApsTotal }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $latestTermLabel }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Latest average</p>
                <p class="mt-2 text-3xl font-bold">{{ $formatPercent($latestAverageMark) }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Excluding Life Orientation</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Selected subjects</p>
                <p class="mt-2 text-3xl font-bold">{{ $selectedSubjects->count() }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $account->grade?->name ?? 'No grade' }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Saved marks</p>
                <p class="mt-2 text-3xl font-bold">{{ $markResults->count() }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Across all terms</p>
            </div>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-bold">Account details</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4 border-b border-neutral-100 pb-3">
                        <dt class="font-semibold text-neutral-500">Full name</dt>
                        <dd class="text-right font-bold text-neutral-900">{{ $account->name ?: 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-neutral-100 pb-3">
                        <dt class="font-semibold text-neutral-500">First / last</dt>
                        <dd class="text-right font-bold text-neutral-900">{{ $account->first_name ?: 'N/A' }} {{ $account->last_name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-neutral-100 pb-3">
                        <dt class="font-semibold text-neutral-500">Curriculum</dt>
                        <dd class="text-right font-bold text-neutral-900">{{ $account->curriculum?->abbreviation ?? $account->curriculum?->name ?? 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-neutral-100 pb-3">
                        <dt class="font-semibold text-neutral-500">Grade</dt>
                        <dd class="text-right font-bold text-neutral-900">{{ $account->grade?->name ?? 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-neutral-100 pb-3">
                        <dt class="font-semibold text-neutral-500">Province</dt>
                        <dd class="text-right font-bold text-neutral-900">{{ $account->province?->name ?? 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-neutral-100 pb-3">
                        <dt class="font-semibold text-neutral-500">School</dt>
                        <dd class="text-right font-bold text-neutral-900">{{ $account->school?->name ?? 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-neutral-100 pb-3">
                        <dt class="font-semibold text-neutral-500">Created</dt>
                        <dd class="text-right font-bold text-neutral-900">{{ $account->created_at?->format('d M Y H:i') ?? 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="font-semibold text-neutral-500">Last login</dt>
                        <dd class="text-right font-bold text-neutral-900">{{ $account->last_login_at?->format('d M Y H:i') ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold">Latest marks</h2>
                        <p class="mt-1 text-sm text-neutral-500">{{ $latestTermLabel }}</p>
                    </div>
                    <span class="rounded-full bg-[#F3F7FC] px-3 py-1 text-xs font-bold text-[#01225E]">APS {{ $latestApsTotal }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                                <th class="py-3 pr-3">Subject</th>
                                <th class="px-3 py-3">Mark</th>
                                <th class="px-3 py-3">APS</th>
                                <th class="py-3 pl-3">Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($latestMarks as $result)
                                <tr class="border-b border-neutral-100">
                                    <td class="py-3 pr-3">
                                        <p class="font-bold text-neutral-950">{{ $result->subject?->name ?? 'Unknown subject' }}</p>
                                        <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $result->subject?->code ?? $result->subject?->abbreviation ?? 'No code' }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-sm font-bold text-neutral-900">{{ $formatPercent($result->mark) }}</td>
                                    <td class="px-3 py-3 text-sm font-bold text-neutral-900">{{ $result->aps_score ?? 'N/A' }}</td>
                                    <td class="py-3 pl-3 text-sm font-semibold text-neutral-600">{{ $result->updated_at?->format('d M H:i') ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-sm font-semibold text-neutral-500">No marks saved yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="mt-6 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold">Selected subjects</h2>
                <p class="mt-1 text-sm text-neutral-500">Subjects this account chose for their grade.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @forelse ($selectedSubjects as $preference)
                    <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                        <p class="font-bold text-neutral-950">{{ $preference->subject?->name ?? 'Unknown subject' }}</p>
                        <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $preference->subject?->code ?? $preference->subject?->abbreviation ?? 'No code' }}</p>
                    </div>
                @empty
                    <p class="rounded-xl bg-neutral-50 px-4 py-6 text-sm font-semibold text-neutral-500 sm:col-span-2 lg:col-span-4">No subjects selected yet.</p>
                @endforelse
            </div>
        </section>

        <section class="mt-6 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold">All saved marks</h2>
                <p class="mt-1 text-sm text-neutral-500">Grouped by grade and term.</p>
            </div>

            @forelse ($marksByTerm as $termLabel => $results)
                <div class="mb-5 last:mb-0">
                    <h3 class="mb-2 text-sm font-bold text-[#01225E]">{{ $termLabel }}</h3>
                    <div class="overflow-x-auto rounded-xl border border-neutral-200">
                        <table class="w-full min-w-[720px] text-left">
                            <thead>
                                <tr class="border-b border-neutral-200 bg-neutral-50 text-xs uppercase text-neutral-500">
                                    <th class="px-3 py-3">Subject</th>
                                    <th class="px-3 py-3">Mark</th>
                                    <th class="px-3 py-3">APS</th>
                                    <th class="px-3 py-3">Saved</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($results as $result)
                                    <tr class="border-b border-neutral-100 last:border-b-0">
                                        <td class="px-3 py-3 font-bold text-neutral-950">{{ $result->subject?->name ?? 'Unknown subject' }}</td>
                                        <td class="px-3 py-3 text-sm font-semibold text-neutral-700">{{ $formatPercent($result->mark) }}</td>
                                        <td class="px-3 py-3 text-sm font-semibold text-neutral-700">{{ $result->aps_score ?? 'N/A' }}</td>
                                        <td class="px-3 py-3 text-sm font-semibold text-neutral-600">{{ $result->updated_at?->format('d M Y H:i') ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <p class="rounded-xl bg-neutral-50 px-4 py-6 text-sm font-semibold text-neutral-500">No saved marks found for this account.</p>
            @endforelse
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-xl font-bold">Recent visits</h2>
                    <p class="mt-1 text-sm text-neutral-500">Latest captured pages for this account.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                                <th class="py-3 pr-3">Page</th>
                                <th class="px-3 py-3">Device</th>
                                <th class="py-3 pl-3">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentVisits as $visit)
                                @php
                                    $pageDetail = $visit->pageDetail();
                                @endphp
                                <tr class="border-b border-neutral-100 align-top">
                                    <td class="py-3 pr-3">
                                        <p class="max-w-xs truncate text-sm font-bold text-neutral-950">{{ $visit->pageLabel() }}</p>
                                        @if ($pageDetail)
                                            <p class="mt-1 max-w-xs truncate text-xs font-semibold text-neutral-500">URL {{ $pageDetail }}</p>
                                        @endif
                                        <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $visit->ip_address ?? 'Unknown IP' }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-sm font-semibold text-neutral-700">{{ $visit->device_type ?? 'Unknown' }} - {{ $visit->browser ?? 'Unknown' }}</td>
                                    <td class="py-3 pl-3 text-sm font-semibold text-neutral-600">{{ $visit->visited_at?->format('d M H:i') ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-sm font-semibold text-neutral-500">No visits captured for this account yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-xl font-bold">Mark audits</h2>
                    <p class="mt-1 text-sm text-neutral-500">Every saved mark event captured so far.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[620px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                                <th class="py-3 pr-3">Term / grade</th>
                                <th class="px-3 py-3">Changed</th>
                                <th class="py-3 pl-3">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($markAuditLogs as $log)
                                @php
                                    $metadata = $log->metadata ?? [];
                                    $changedMarks = $metadata['changed_marks'] ?? [];
                                    $removedMarks = $metadata['removed_marks'] ?? [];
                                @endphp
                                <tr class="border-b border-neutral-100">
                                    <td class="py-3 pr-3 text-sm font-semibold text-neutral-700">Term {{ $metadata['term_id'] ?? 'N/A' }} - Grade {{ $metadata['grade_id'] ?? 'N/A' }}</td>
                                    <td class="px-3 py-3 text-sm font-semibold text-neutral-700">{{ count($changedMarks) }} saved, {{ count($removedMarks) }} removed</td>
                                    <td class="py-3 pl-3 text-sm font-semibold text-neutral-600">{{ $log->created_at?->format('d M H:i') ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-sm font-semibold text-neutral-500">No mark audit entries for this account yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="mt-6 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold">Recent practice</h2>
                <p class="mt-1 text-sm text-neutral-500">Latest quiz or practice sessions tied to the account.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                            <th class="py-3 pr-3">Session</th>
                            <th class="px-3 py-3">Subject</th>
                            <th class="px-3 py-3">Score</th>
                            <th class="px-3 py-3">Completed</th>
                            <th class="py-3 pl-3">Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentExamSessions as $session)
                            <tr class="border-b border-neutral-100">
                                <td class="py-3 pr-3">
                                    <p class="font-bold text-neutral-950">{{ $session->title ?? 'Untitled session' }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $session->quiz_type ?? 'Practice' }} - {{ $session->source ?? 'Chamu' }}</p>
                                </td>
                                <td class="px-3 py-3 text-sm font-semibold text-neutral-700">{{ $session->subject_name ?? 'No subject' }}</td>
                                <td class="px-3 py-3 text-sm font-semibold text-neutral-700">
                                    @if ($session->percentage !== null)
                                        {{ $formatPercent($session->percentage) }}
                                    @else
                                        {{ $session->score ?? 0 }}/{{ $session->total_marks ?? 0 }}
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-sm font-semibold text-neutral-600">{{ $session->completed_at ? \Illuminate\Support\Carbon::parse($session->completed_at)->format('d M H:i') : 'Not completed' }}</td>
                                <td class="py-3 pl-3 text-sm font-semibold text-neutral-600">{{ $session->updated_at ? \Illuminate\Support\Carbon::parse($session->updated_at)->format('d M H:i') : 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-sm font-semibold text-neutral-500">No practice sessions found for this account yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
@endsection
