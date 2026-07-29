<?php

namespace App\Http\Controllers\Models;

use App\Models\Faculty;
use Illuminate\Http\Request;

class FacultyController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Faculty::class);
    }

    public function create()
    {
        return $this->createFor(Faculty::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Faculty::class);
    }

    public function edit(Faculty $faculty)
    {
        return $this->editFor(Faculty::class, $faculty);
    }

    public function update(Request $request, Faculty $faculty)
    {
        return $this->updateFor($request, Faculty::class, $faculty);
    }
}
