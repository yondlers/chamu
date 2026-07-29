<?php

namespace App\Http\Controllers\Models;

use App\Models\QualificationSubjectRequirement;
use Illuminate\Http\Request;

class QualificationSubjectRequirementController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(QualificationSubjectRequirement::class);
    }

    public function create()
    {
        return $this->createFor(QualificationSubjectRequirement::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, QualificationSubjectRequirement::class);
    }

    public function edit(QualificationSubjectRequirement $qualificationSubjectRequirement)
    {
        return $this->editFor(QualificationSubjectRequirement::class, $qualificationSubjectRequirement);
    }

    public function update(Request $request, QualificationSubjectRequirement $qualificationSubjectRequirement)
    {
        return $this->updateFor($request, QualificationSubjectRequirement::class, $qualificationSubjectRequirement);
    }
}
