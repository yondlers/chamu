<?php

namespace App\Http\Controllers\Models;

use App\Models\School;
use Illuminate\Http\Request;

class SchoolController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(School::class);
    }

    public function create()
    {
        return $this->createFor(School::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, School::class);
    }

    public function edit(School $school)
    {
        return $this->editFor(School::class, $school);
    }

    public function update(Request $request, School $school)
    {
        return $this->updateFor($request, School::class, $school);
    }
}
