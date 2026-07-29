<?php

namespace App\Http\Controllers\Models;

use App\Models\UserSubjectResult;
use Illuminate\Http\Request;

class UserSubjectResultController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(UserSubjectResult::class);
    }

    public function create()
    {
        return $this->createFor(UserSubjectResult::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, UserSubjectResult::class);
    }

    public function edit(UserSubjectResult $userSubjectResult)
    {
        return $this->editFor(UserSubjectResult::class, $userSubjectResult);
    }

    public function update(Request $request, UserSubjectResult $userSubjectResult)
    {
        return $this->updateFor($request, UserSubjectResult::class, $userSubjectResult);
    }
}
