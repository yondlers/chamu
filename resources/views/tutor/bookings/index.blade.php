@extends('layouts.app')

@section('title', 'Tutor bookings · Chamu')

@section('content')
    <main class="mx-auto max-w-5xl px-5 py-10 lg:px-8">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-[#01225E]/70">Bookings</p>
        <h1 class="mt-2 text-3xl font-black">Tutor bookings</h1>
        <p class="mt-2 text-neutral-600">Booking records are ready. Public booking for learners will open with the Tutor section.</p>

        <div class="mt-8 overflow-hidden rounded-2xl border border-neutral-200 bg-white">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-neutral-50 text-xs font-bold uppercase tracking-wide text-neutral-500">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Time</th>
                        <th class="px-4 py-3">Subject</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr class="border-t border-neutral-100">
                            <td class="px-4 py-3 font-semibold">{{ $booking->booking_date?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">{{ substr((string) $booking->start_time, 0, 5) }} – {{ substr((string) $booking->end_time, 0, 5) }}</td>
                            <td class="px-4 py-3">{{ $booking->subject?->name ?: ($booking->subject_other ?: '—') }}</td>
                            <td class="px-4 py-3 capitalize">{{ $booking->tutorBookingStatus?->name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-neutral-500">No bookings yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $bookings->links() }}</div>
    </main>
@endsection
