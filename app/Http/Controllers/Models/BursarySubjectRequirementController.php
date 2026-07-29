<?php

namespace App\Http\Controllers\Models;

use App\Models\BursarySubjectRequirement;
use Illuminate\Http\Request;

class BursarySubjectRequirementController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(BursarySubjectRequirement::class);
    }

    public function create()
    {
        return $this->createFor(BursarySubjectRequirement::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, BursarySubjectRequirement::class);
    }

    public function edit(BursarySubjectRequirement $bursarySubjectRequirement)
    {
        return $this->editFor(BursarySubjectRequirement::class, $bursarySubjectRequirement);
    }

    public function update(Request $request, BursarySubjectRequirement $bursarySubjectRequirement)
    {
        return $this->updateFor($request, BursarySubjectRequirement::class, $bursarySubjectRequirement);
    }
}
