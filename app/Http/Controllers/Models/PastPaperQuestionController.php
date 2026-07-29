<?php

namespace App\Http\Controllers\Models;

use App\Models\PastPaperQuestion;
use Illuminate\Http\Request;

class PastPaperQuestionController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(PastPaperQuestion::class);
    }

    public function create()
    {
        return $this->createFor(PastPaperQuestion::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, PastPaperQuestion::class);
    }

    public function edit(PastPaperQuestion $pastPaperQuestion)
    {
        return $this->editFor(PastPaperQuestion::class, $pastPaperQuestion);
    }

    public function update(Request $request, PastPaperQuestion $pastPaperQuestion)
    {
        return $this->updateFor($request, PastPaperQuestion::class, $pastPaperQuestion);
    }
}
