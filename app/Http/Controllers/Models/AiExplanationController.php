<?php

namespace App\Http\Controllers\Models;

use App\Models\AiExplanation;
use Illuminate\Http\Request;

class AiExplanationController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(AiExplanation::class);
    }

    public function create()
    {
        return $this->createFor(AiExplanation::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, AiExplanation::class);
    }

    public function edit(AiExplanation $aiExplanation)
    {
        return $this->editFor(AiExplanation::class, $aiExplanation);
    }

    public function update(Request $request, AiExplanation $aiExplanation)
    {
        return $this->updateFor($request, AiExplanation::class, $aiExplanation);
    }
}
