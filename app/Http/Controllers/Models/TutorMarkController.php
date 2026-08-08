<?php

namespace App\Http\Controllers\Models;

use App\Models\TutorMark;
use Illuminate\Http\Request;

class TutorMarkController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(TutorMark::class);
    }

    public function create()
    {
        return $this->createFor(TutorMark::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, TutorMark::class);
    }

    public function edit(TutorMark $tutorMark)
    {
        return $this->editFor(TutorMark::class, $tutorMark);
    }

    public function update(Request $request, TutorMark $tutorMark)
    {
        return $this->updateFor($request, TutorMark::class, $tutorMark);
    }
}
