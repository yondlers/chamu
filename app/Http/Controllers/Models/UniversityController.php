<?php

namespace App\Http\Controllers\Models;

use App\Models\University;
use Illuminate\Http\Request;

class UniversityController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(University::class);
    }

    public function create()
    {
        return $this->createFor(University::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, University::class);
    }

    public function edit(University $university)
    {
        return $this->editFor(University::class, $university);
    }

    public function update(Request $request, University $university)
    {
        return $this->updateFor($request, University::class, $university);
    }
}
