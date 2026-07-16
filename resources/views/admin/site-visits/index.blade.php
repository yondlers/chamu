@extends('layouts.app')

@section('title', "Who's on the Site - Admin - Chamu")

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-5 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#01225E] hover:underline">
                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Admin overview
                </a>
                <p class="mt-5 text-sm font-bold text-[#01225E]">Site visits</p>
                <h1 class="mt-1 text-3xl font-bold">Who's on the site</h1>
                <p class="mt-2 max-w-3xl text-neutral-500">All captured page views, not limited to the last 10 minutes.</p>
            </div>
        </div>

        <section class="mb-6 grid gap-3 md:grid-cols-3">
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Total visits</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($totalVisits) }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Guest visits</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($guestVisits) }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Logged-in visits</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($userVisits) }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold">Visit list</h2>
                <p class="mt-1 text-sm text-neutral-500">Showing {{ $siteVisits->firstItem() ?? 0 }}-{{ $siteVisits->lastItem() ?? 0 }} of {{ number_format($siteVisits->total()) }} visits.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1080px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                            <th class="py-3 pr-3">Visitor</th>
                            <th class="px-3 py-3">Method</th>
                            <th class="px-3 py-3">Page</th>
                            <th class="px-3 py-3">Device</th>
                            <th class="px-3 py-3">Seen</th>
                            <th class="py-3 pl-3 text-right">More</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($siteVisits as $visit)
                            @php($pageDetail = $visit->pageDetail())
                            <tr class="border-b border-neutral-100 align-top">
                                <td class="py-4 pr-3">
                                    <p class="font-bold text-neutral-950">{{ $visit->user?->name ?? 'Guest visitor' }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $visit->ip_address ?? 'Unknown IP' }}</p>
                                </td>
                                <td class="px-3 py-4 text-sm font-bold text-neutral-700">{{ $visit->method }}</td>
                                <td class="px-3 py-4">
                                    <p class="max-w-md truncate text-sm font-semibold text-neutral-900">{{ $visit->pageLabel() }}</p>
                                    @if ($pageDetail)
                                        <p class="mt-1 max-w-md truncate text-xs font-semibold text-neutral-500">URL {{ $pageDetail }}</p>
                                    @endif
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $visit->route_name ?? 'No route name' }}</p>
                                </td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">{{ $visit->device_type ?? 'Unknown' }} - {{ $visit->browser ?? 'Unknown' }}</td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">{{ $visit->visited_at?->format('d M Y H:i') ?? 'N/A' }}</td>
                                <td class="py-4 pl-3 text-right">
                                    <a href="{{ route('admin.site-visits.show', $visit) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 px-3 py-2 text-sm font-bold hover:bg-neutral-50">
                                        More <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm font-semibold text-neutral-500">No visits captured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($siteVisits->hasPages())
                <div class="mt-5">
                    {{ $siteVisits->links() }}
                </div>
            @endif
        </section>
    </main>
@endsection
