<?php

namespace App\Http\Controllers\Models;

use App\Models\TutorStatus;
use Illuminate\Http\Request;

class TutorStatusController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(TutorStatus::class);
    }

    public function create()
    {
        return $this->createFor(TutorStatus::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, TutorStatus::class);
    }

    public function edit(TutorStatus $tutorStatus)
    {
        return $this->editFor(TutorStatus::class, $tutorStatus);
    }

    public function update(Request $request, TutorStatus $tutorStatus)
    {
        return $this->updateFor($request, TutorStatus::class, $tutorStatus);
    }
}
