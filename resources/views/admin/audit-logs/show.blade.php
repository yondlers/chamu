@extends('layouts.app')

@section('title', 'Audit Log #'.$auditLog->id.' - Admin - Chamu')

@section('content')
    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-5 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#01225E] hover:underline">
                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Audit logs
                </a>
                <p class="mt-5 text-sm font-bold text-[#01225E]">Audit log details</p>
                <h1 class="mt-1 text-3xl font-bold">{{ $auditLog->name }}</h1>
                <p class="mt-2 max-w-3xl text-neutral-500">{{ $auditLog->description ?: ($auditLog->event ?? 'No event key') }}</p>
            </div>
        </div>

        <section class="mb-6 grid gap-3 md:grid-cols-3">
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">User</p>
                <p class="mt-2 text-lg font-bold">{{ $auditLog->user?->name ?? 'No user' }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $auditLog->user?->email ?? 'N/A' }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Event</p>
                <p class="mt-2 text-lg font-bold">{{ $auditLog->event ?? 'N/A' }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $auditLog->ip_address ?? 'Unknown IP' }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Created</p>
                <p class="mt-2 text-lg font-bold">{{ $auditLog->created_at?->format('d M Y H:i') ?? 'N/A' }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">{{ class_basename($auditLog->auditable_type ?? '') ?: 'No auditable model' }}</p>
            </div>
        </section>

        @include('admin.partials.model-fields', ['model' => $auditLog])
    </main>
@endsection
