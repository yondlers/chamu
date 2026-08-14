<?php

namespace App\Http\Controllers\Models;

use App\Models\UserStudentReview;
use Illuminate\Http\Request;

class UserStudentReviewController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(UserStudentReview::class);
    }

    public function create()
    {
        return $this->createFor(UserStudentReview::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, UserStudentReview::class);
    }

    public function edit(UserStudentReview $userStudentReview)
    {
        return $this->editFor(UserStudentReview::class, $userStudentReview);
    }

    public function update(Request $request, UserStudentReview $userStudentReview)
    {
        return $this->updateFor($request, UserStudentReview::class, $userStudentReview);
    }
}
