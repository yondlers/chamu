<?php

namespace App\Http\Controllers\Models;

use App\Models\Answer;
use Illuminate\Http\Request;

class AnswerController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Answer::class);
    }

    public function create()
    {
        return $this->createFor(Answer::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Answer::class);
    }

    public function edit(Answer $answer)
    {
        return $this->editFor(Answer::class, $answer);
    }

    public function update(Request $request, Answer $answer)
    {
        return $this->updateFor($request, Answer::class, $answer);
    }
}
