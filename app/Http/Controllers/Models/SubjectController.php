<?php

namespace App\Http\Controllers\Models;

use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Subject::class);
    }

    public function create()
    {
        return $this->createFor(Subject::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Subject::class);
    }

    public function edit(Subject $subject)
    {
        return $this->editFor(Subject::class, $subject);
    }

    public function update(Request $request, Subject $subject)
    {
        return $this->updateFor($request, Subject::class, $subject);
    }
}
