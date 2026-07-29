<?php

namespace App\Http\Controllers\Models;

use App\Models\AdmissionRule;
use Illuminate\Http\Request;

class AdmissionRuleController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(AdmissionRule::class);
    }

    public function create()
    {
        return $this->createFor(AdmissionRule::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, AdmissionRule::class);
    }

    public function edit(AdmissionRule $admissionRule)
    {
        return $this->editFor(AdmissionRule::class, $admissionRule);
    }

    public function update(Request $request, AdmissionRule $admissionRule)
    {
        return $this->updateFor($request, AdmissionRule::class, $admissionRule);
    }
}
