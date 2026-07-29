<?php

namespace App\Http\Controllers\Models;

use App\Models\SubQuestion;
use Illuminate\Http\Request;

class SubQuestionController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(SubQuestion::class);
    }

    public function create()
    {
        return $this->createFor(SubQuestion::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, SubQuestion::class);
    }

    public function edit(SubQuestion $subQuestion)
    {
        return $this->editFor(SubQuestion::class, $subQuestion);
    }

    public function update(Request $request, SubQuestion $subQuestion)
    {
        return $this->updateFor($request, SubQuestion::class, $subQuestion);
    }
}
