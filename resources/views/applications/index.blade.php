@extends('layouts.app')

@section('title', 'Applications · Chamu')

@section('content')
    <main class="mx-auto max-w-6xl px-5 py-8 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-[#01225E]">Your Chamu trail</p>
                <h1 class="mt-1 text-3xl font-black">My applications</h1>
                <p class="mt-2 max-w-2xl text-neutral-500">Every bursary application Chamu sends, prepares, or needs attention on will appear here.</p>
            </div>
            <a href="{{ route('bursaries.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#001A48]">
                Browse funding <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
            </a>
        </div>

        <section class="space-y-4">
            @forelse ($applications as $application)
                @php
                    $statusLabel = match ($application->status) {
                        'submitted' => 'Sent',
                        'postal_ready' => 'Postal ready',
                        'failed' => 'Needs attention',
                        default => \Illuminate\Support\Str::of($application->status)->replace('_', ' ')->title(),
                    };
                    $statusClass = match ($application->status) {
                        'submitted' => 'bg-emerald-50 text-emerald-800 ring-emerald-100',
                        'postal_ready' => 'bg-sky-50 text-sky-800 ring-sky-100',
                        'failed' => 'bg-rose-50 text-rose-800 ring-rose-100',
                        default => 'bg-neutral-100 text-neutral-700 ring-neutral-200',
                    };
                    $deliveryLabel = $application->delivery_type === 'postal' ? 'Postal application' : 'Email application';
                    $deliveryIcon = $application->delivery_type === 'postal' ? 'package-check' : 'mail-check';
                    $applicationDate = $application->submitted_at ?? $application->created_at;
                    $documentsCount = (int) $application->documents_count;
                @endphp

                <article class="rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-black ring-1 {{ $statusClass }}">
                                    <i data-lucide="{{ $application->status === 'failed' ? 'triangle-alert' : 'check-circle-2' }}" style="width:14px;height:14px;"></i>
                                    {{ $statusLabel }}
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-700">
                                    <i data-lucide="{{ $deliveryIcon }}" style="width:14px;height:14px;"></i>
                                    {{ $deliveryLabel }}
                                </span>
                            </div>

                            <h2 class="mt-4 text-2xl font-black leading-tight">{{ $application->bursary_title }}</h2>
                            <p class="mt-1 font-semibold text-neutral-500">{{ $application->company_name ?? 'Bursary provider' }}</p>

                            <div class="mt-4 grid gap-3 text-sm text-neutral-600 sm:grid-cols-2 lg:grid-cols-3">
                                <div class="rounded-xl bg-neutral-50 p-3">
                                    <p class="text-xs font-black uppercase tracking-[0.12em] text-neutral-500">Documents</p>
                                    <p class="mt-1 font-bold text-neutral-900">{{ $documentsCount }} {{ \Illuminate\Support\Str::plural('document', $documentsCount) }}</p>
                                </div>
                                <div class="rounded-xl bg-neutral-50 p-3">
                                    <p class="text-xs font-black uppercase tracking-[0.12em] text-neutral-500">Receipt</p>
                                    <p class="mt-1 font-bold text-neutral-900">{{ $application->receipt_sent_at ? 'Receipt emailed' : 'Receipt pending' }}</p>
                                </div>
                                <div class="rounded-xl bg-neutral-50 p-3">
                                    <p class="text-xs font-black uppercase tracking-[0.12em] text-neutral-500">Applied</p>
                                    <p class="mt-1 font-bold text-neutral-900">{{ $applicationDate ? \Illuminate\Support\Carbon::parse($applicationDate)->format('d M Y') : 'Not dated' }}</p>
                                </div>
                            </div>
                        </div>

                        <aside class="w-full rounded-2xl border border-neutral-200 bg-neutral-50 p-4 lg:w-80">
                            <p class="text-xs font-black uppercase tracking-[0.12em] text-neutral-500">Where it went</p>
                            @if ($application->delivery_type === 'postal')
                                <p class="mt-2 text-sm font-semibold text-neutral-800">{{ $application->provider_postal_address ?: 'Postal destination saved with bursary.' }}</p>
                                <p class="mt-2 text-xs text-neutral-500">Chamu has prepared the postal pack trail for this application.</p>
                            @else
                                <p class="mt-2 text-sm font-semibold text-neutral-800">{{ $application->provider_email ?: 'Provider email saved with bursary.' }}</p>
                                <p class="mt-2 text-xs text-neutral-500">The student email is kept as Reply-To on Chamu-managed submissions.</p>
                            @endif

                            <a href="{{ route('bursaries.show', $application->bursary_id) }}" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-[#01225E] ring-1 ring-neutral-200 hover:bg-neutral-100">
                                View bursary <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                            </a>
                        </aside>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-neutral-300 bg-white p-10 text-center">
                    <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-[#01225E] text-white">
                        <i data-lucide="folder-check" style="width:24px;height:24px;"></i>
                    </span>
                    <h2 class="mt-4 text-2xl font-black">No applications yet</h2>
                    <p class="mx-auto mt-2 max-w-md text-neutral-500">Apply with Chamu from a bursary details page and your receipt, documents count, and status will show here.</p>
                    <a href="{{ route('bursaries.index') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#001A48]">
                        Find bursaries <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                    </a>
                </div>
            @endforelse
        </section>

        @if ($applications->hasPages())
            <div class="mt-6">
                {{ $applications->links() }}
            </div>
        @endif
    </main>
@endsection
