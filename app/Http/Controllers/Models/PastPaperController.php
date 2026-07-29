<?php

namespace App\Http\Controllers\Models;

use App\Models\PastPaper;
use Illuminate\Http\Request;

class PastPaperController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(PastPaper::class);
    }

    public function create()
    {
        return $this->createFor(PastPaper::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, PastPaper::class);
    }

    public function edit(PastPaper $pastPaper)
    {
        return $this->editFor(PastPaper::class, $pastPaper);
    }

    public function update(Request $request, PastPaper $pastPaper)
    {
        return $this->updateFor($request, PastPaper::class, $pastPaper);
    }
}
