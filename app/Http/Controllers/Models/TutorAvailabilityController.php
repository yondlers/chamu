<?php

namespace App\Http\Controllers\Models;

use App\Models\TutorAvailability;
use Illuminate\Http\Request;

class TutorAvailabilityController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(TutorAvailability::class);
    }

    public function create()
    {
        return $this->createFor(TutorAvailability::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, TutorAvailability::class);
    }

    public function edit(TutorAvailability $tutorAvailability)
    {
        return $this->editFor(TutorAvailability::class, $tutorAvailability);
    }

    public function update(Request $request, TutorAvailability $tutorAvailability)
    {
        return $this->updateFor($request, TutorAvailability::class, $tutorAvailability);
    }
}
