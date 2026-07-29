<?php

namespace App\Http\Controllers\Models;

use App\Models\UniversityAdmissionRule;
use Illuminate\Http\Request;

class UniversityAdmissionRuleController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(UniversityAdmissionRule::class);
    }

    public function create()
    {
        return $this->createFor(UniversityAdmissionRule::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, UniversityAdmissionRule::class);
    }

    public function edit(UniversityAdmissionRule $universityAdmissionRule)
    {
        return $this->editFor(UniversityAdmissionRule::class, $universityAdmissionRule);
    }

    public function update(Request $request, UniversityAdmissionRule $universityAdmissionRule)
    {
        return $this->updateFor($request, UniversityAdmissionRule::class, $universityAdmissionRule);
    }
}
