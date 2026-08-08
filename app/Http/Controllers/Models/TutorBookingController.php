<?php

namespace App\Http\Controllers\Models;

use App\Models\TutorBooking;
use Illuminate\Http\Request;

class TutorBookingController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(TutorBooking::class);
    }

    public function create()
    {
        return $this->createFor(TutorBooking::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, TutorBooking::class);
    }

    public function edit(TutorBooking $tutorBooking)
    {
        return $this->editFor(TutorBooking::class, $tutorBooking);
    }

    public function update(Request $request, TutorBooking $tutorBooking)
    {
        return $this->updateFor($request, TutorBooking::class, $tutorBooking);
    }
}
