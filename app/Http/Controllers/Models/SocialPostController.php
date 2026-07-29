<?php

namespace App\Http\Controllers\Models;

use App\Models\SocialPost;
use Illuminate\Http\Request;

class SocialPostController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(SocialPost::class);
    }

    public function create()
    {
        return $this->createFor(SocialPost::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, SocialPost::class);
    }

    public function edit(SocialPost $socialPost)
    {
        return $this->editFor(SocialPost::class, $socialPost);
    }

    public function update(Request $request, SocialPost $socialPost)
    {
        return $this->updateFor($request, SocialPost::class, $socialPost);
    }
}
