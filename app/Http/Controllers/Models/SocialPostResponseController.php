<?php

namespace App\Http\Controllers\Models;

use App\Models\SocialPostResponse;
use Illuminate\Http\Request;

class SocialPostResponseController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(SocialPostResponse::class);
    }

    public function create()
    {
        return $this->createFor(SocialPostResponse::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, SocialPostResponse::class);
    }

    public function edit(SocialPostResponse $socialPostResponse)
    {
        return $this->editFor(SocialPostResponse::class, $socialPostResponse);
    }

    public function update(Request $request, SocialPostResponse $socialPostResponse)
    {
        return $this->updateFor($request, SocialPostResponse::class, $socialPostResponse);
    }
}
