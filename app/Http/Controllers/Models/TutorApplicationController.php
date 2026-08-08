<?php

namespace App\Http\Controllers\Models;

use App\Models\TutorApplication;
use Illuminate\Http\Request;

class TutorApplicationController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(TutorApplication::class);
    }

    public function create()
    {
        return $this->createFor(TutorApplication::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, TutorApplication::class);
    }

    public function edit(TutorApplication $tutorApplication)
    {
        return $this->editFor(TutorApplication::class, $tutorApplication);
    }

    public function update(Request $request, TutorApplication $tutorApplication)
    {
        return $this->updateFor($request, TutorApplication::class, $tutorApplication);
    }
}
