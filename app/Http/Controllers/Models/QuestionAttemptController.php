<?php

namespace App\Http\Controllers\Models;

use App\Models\QuestionAttempt;
use Illuminate\Http\Request;

class QuestionAttemptController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(QuestionAttempt::class);
    }

    public function create()
    {
        return $this->createFor(QuestionAttempt::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, QuestionAttempt::class);
    }

    public function edit(QuestionAttempt $questionAttempt)
    {
        return $this->editFor(QuestionAttempt::class, $questionAttempt);
    }

    public function update(Request $request, QuestionAttempt $questionAttempt)
    {
        return $this->updateFor($request, QuestionAttempt::class, $questionAttempt);
    }
}
