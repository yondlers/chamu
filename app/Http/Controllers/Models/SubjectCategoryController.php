<?php

namespace App\Http\Controllers\Models;

use App\Models\SubjectCategory;
use Illuminate\Http\Request;

class SubjectCategoryController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(SubjectCategory::class);
    }

    public function create()
    {
        return $this->createFor(SubjectCategory::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, SubjectCategory::class);
    }

    public function edit(SubjectCategory $subjectCategory)
    {
        return $this->editFor(SubjectCategory::class, $subjectCategory);
    }

    public function update(Request $request, SubjectCategory $subjectCategory)
    {
        return $this->updateFor($request, SubjectCategory::class, $subjectCategory);
    }
}
