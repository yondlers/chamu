<?php

namespace App\Http\Controllers\Models;

use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Grade::class);
    }

    public function create()
    {
        return $this->createFor(Grade::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Grade::class);
    }

    public function edit(Grade $grade)
    {
        return $this->editFor(Grade::class, $grade);
    }

    public function update(Request $request, Grade $grade)
    {
        return $this->updateFor($request, Grade::class, $grade);
    }
}
