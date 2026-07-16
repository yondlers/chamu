@extends('layouts.app')

@section('title', 'Site Visit #'.$siteVisit->id.' - Admin - Chamu')

@section('content')
    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-5 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <a href="{{ route('admin.site-visits.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#01225E] hover:underline">
                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Site visits
                </a>
                <p class="mt-5 text-sm font-bold text-[#01225E]">Site visit details</p>
                <h1 class="mt-1 text-3xl font-bold">Visit #{{ $siteVisit->id }}</h1>
                <p class="mt-2 max-w-3xl text-neutral-500">{{ $siteVisit->pageLabel() }}</p>
            </div>
        </div>

        <section class="mb-6 grid gap-3 md:grid-cols-3">
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Visitor</p>
                <p class="mt-2 text-lg font-bold">{{ $siteVisit->user?->name ?? 'Guest visitor' }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $siteVisit->user?->email ?? 'Not logged in' }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Device</p>
                <p class="mt-2 text-lg font-bold">{{ $siteVisit->device_type ?? 'Unknown' }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $siteVisit->platform ?? 'Unknown platform' }} - {{ $siteVisit->browser ?? 'Unknown browser' }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Seen</p>
                <p class="mt-2 text-lg font-bold">{{ $siteVisit->visited_at?->format('d M Y H:i') ?? 'N/A' }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $siteVisit->ip_address ?? 'Unknown IP' }}</p>
            </div>
        </section>

        @include('admin.partials.model-fields', ['model' => $siteVisit])
    </main>
@endsection
