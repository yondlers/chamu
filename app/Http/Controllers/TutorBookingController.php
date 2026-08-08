<?php

namespace App\Http\Controllers;

use App\Models\TutorBooking;
use App\Models\TutorBookingStatus;
use Illuminate\Http\Request;

class TutorBookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = TutorBooking::query()
            ->with([
                'tutorApplication.user',
                'learner',
                'subject',
                'tutorBookingStatus',
                'tutorAvailability',
            ])
            ->when($request->user()?->isTutor(), function ($query) use ($request) {
                $query->whereHas('tutorApplication', fn ($inner) => $inner->where('user_id', $request->user()->id));
            }, function ($query) use ($request) {
                $query->where('learner_user_id', $request->user()->id);
            })
            ->latest('booking_date')
            ->paginate(20);

        return view('tutor.bookings.index', [
            'bookings' => $bookings,
            'statuses' => TutorBookingStatus::query()->orderBy('name')->get(),
        ]);
    }
}
