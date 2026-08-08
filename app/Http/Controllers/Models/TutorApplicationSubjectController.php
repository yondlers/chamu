<?php

namespace App\Http\Controllers\Models;

use App\Models\TutorApplicationSubject;
use Illuminate\Http\Request;

class TutorApplicationSubjectController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(TutorApplicationSubject::class);
    }

    public function create()
    {
        return $this->createFor(TutorApplicationSubject::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, TutorApplicationSubject::class);
    }

    public function edit(TutorApplicationSubject $tutorApplicationSubject)
    {
        return $this->editFor(TutorApplicationSubject::class, $tutorApplicationSubject);
    }

    public function update(Request $request, TutorApplicationSubject $tutorApplicationSubject)
    {
        return $this->updateFor($request, TutorApplicationSubject::class, $tutorApplicationSubject);
    }
}
