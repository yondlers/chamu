<?php

namespace App\Http\Controllers\Models;

use App\Models\BursaryDocumentRequirement;
use Illuminate\Http\Request;

class BursaryDocumentRequirementController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(BursaryDocumentRequirement::class);
    }

    public function create()
    {
        return $this->createFor(BursaryDocumentRequirement::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, BursaryDocumentRequirement::class);
    }

    public function edit(BursaryDocumentRequirement $bursaryDocumentRequirement)
    {
        return $this->editFor(BursaryDocumentRequirement::class, $bursaryDocumentRequirement);
    }

    public function update(Request $request, BursaryDocumentRequirement $bursaryDocumentRequirement)
    {
        return $this->updateFor($request, BursaryDocumentRequirement::class, $bursaryDocumentRequirement);
    }
}
