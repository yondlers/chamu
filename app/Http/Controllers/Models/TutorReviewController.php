<?php

namespace App\Http\Controllers\Models;

use App\Models\TutorReview;
use Illuminate\Http\Request;

class TutorReviewController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(TutorReview::class);
    }

    public function create()
    {
        return $this->createFor(TutorReview::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, TutorReview::class);
    }

    public function edit(TutorReview $tutorReview)
    {
        return $this->editFor(TutorReview::class, $tutorReview);
    }

    public function update(Request $request, TutorReview $tutorReview)
    {
        return $this->updateFor($request, TutorReview::class, $tutorReview);
    }
}
