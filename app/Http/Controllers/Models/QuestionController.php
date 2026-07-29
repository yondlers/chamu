<?php

namespace App\Http\Controllers\Models;

use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Question::class);
    }

    public function create()
    {
        return $this->createFor(Question::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Question::class);
    }

    public function edit(Question $question)
    {
        return $this->editFor(Question::class, $question);
    }

    public function update(Request $request, Question $question)
    {
        return $this->updateFor($request, Question::class, $question);
    }
}
