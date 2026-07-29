<?php

namespace App\Http\Controllers\Models;

use App\Models\Qualification;
use Illuminate\Http\Request;

class QualificationController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Qualification::class);
    }

    public function create()
    {
        return $this->createFor(Qualification::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Qualification::class);
    }

    public function edit(Qualification $qualification)
    {
        return $this->editFor(Qualification::class, $qualification);
    }

    public function update(Request $request, Qualification $qualification)
    {
        return $this->updateFor($request, Qualification::class, $qualification);
    }
}
