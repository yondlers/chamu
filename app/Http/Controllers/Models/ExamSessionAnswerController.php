<?php

namespace App\Http\Controllers\Models;

use App\Models\ExamSessionAnswer;
use Illuminate\Http\Request;

class ExamSessionAnswerController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(ExamSessionAnswer::class);
    }

    public function create()
    {
        return $this->createFor(ExamSessionAnswer::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, ExamSessionAnswer::class);
    }

    public function edit(ExamSessionAnswer $examSessionAnswer)
    {
        return $this->editFor(ExamSessionAnswer::class, $examSessionAnswer);
    }

    public function update(Request $request, ExamSessionAnswer $examSessionAnswer)
    {
        return $this->updateFor($request, ExamSessionAnswer::class, $examSessionAnswer);
    }
}
