<?php

namespace App\Http\Controllers\Models;

use App\Models\TutorBookingStatus;
use Illuminate\Http\Request;

class TutorBookingStatusController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(TutorBookingStatus::class);
    }

    public function create()
    {
        return $this->createFor(TutorBookingStatus::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, TutorBookingStatus::class);
    }

    public function edit(TutorBookingStatus $tutorBookingStatus)
    {
        return $this->editFor(TutorBookingStatus::class, $tutorBookingStatus);
    }

    public function update(Request $request, TutorBookingStatus $tutorBookingStatus)
    {
        return $this->updateFor($request, TutorBookingStatus::class, $tutorBookingStatus);
    }
}
