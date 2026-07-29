<?php

namespace App\Http\Controllers\Models;

use App\Models\ExamSessionQuestion;
use Illuminate\Http\Request;

class ExamSessionQuestionController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(ExamSessionQuestion::class);
    }

    public function create()
    {
        return $this->createFor(ExamSessionQuestion::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, ExamSessionQuestion::class);
    }

    public function edit(ExamSessionQuestion $examSessionQuestion)
    {
        return $this->editFor(ExamSessionQuestion::class, $examSessionQuestion);
    }

    public function update(Request $request, ExamSessionQuestion $examSessionQuestion)
    {
        return $this->updateFor($request, ExamSessionQuestion::class, $examSessionQuestion);
    }
}
