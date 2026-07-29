<?php

namespace App\Http\Controllers\Models;

use App\Models\QualificationType;
use Illuminate\Http\Request;

class QualificationTypeController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(QualificationType::class);
    }

    public function create()
    {
        return $this->createFor(QualificationType::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, QualificationType::class);
    }

    public function edit(QualificationType $qualificationType)
    {
        return $this->editFor(QualificationType::class, $qualificationType);
    }

    public function update(Request $request, QualificationType $qualificationType)
    {
        return $this->updateFor($request, QualificationType::class, $qualificationType);
    }
}
