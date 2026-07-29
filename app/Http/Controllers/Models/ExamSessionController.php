<?php

namespace App\Http\Controllers\Models;

use App\Models\ExamSession;
use Illuminate\Http\Request;

class ExamSessionController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(ExamSession::class);
    }

    public function create()
    {
        return $this->createFor(ExamSession::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, ExamSession::class);
    }

    public function edit(ExamSession $examSession)
    {
        return $this->editFor(ExamSession::class, $examSession);
    }

    public function update(Request $request, ExamSession $examSession)
    {
        return $this->updateFor($request, ExamSession::class, $examSession);
    }
}
