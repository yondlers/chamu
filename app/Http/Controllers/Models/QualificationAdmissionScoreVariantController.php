<?php

namespace App\Http\Controllers\Models;

use App\Models\QualificationAdmissionScoreVariant;
use Illuminate\Http\Request;

class QualificationAdmissionScoreVariantController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(QualificationAdmissionScoreVariant::class);
    }

    public function create()
    {
        return $this->createFor(QualificationAdmissionScoreVariant::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, QualificationAdmissionScoreVariant::class);
    }

    public function edit(QualificationAdmissionScoreVariant $qualificationAdmissionScoreVariant)
    {
        return $this->editFor(QualificationAdmissionScoreVariant::class, $qualificationAdmissionScoreVariant);
    }

    public function update(Request $request, QualificationAdmissionScoreVariant $qualificationAdmissionScoreVariant)
    {
        return $this->updateFor($request, QualificationAdmissionScoreVariant::class, $qualificationAdmissionScoreVariant);
    }
}
