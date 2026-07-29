<?php

namespace App\Http\Controllers\Models;

use App\Models\Topic;
use Illuminate\Http\Request;

class TopicController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Topic::class);
    }

    public function create()
    {
        return $this->createFor(Topic::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Topic::class);
    }

    public function edit(Topic $topic)
    {
        return $this->editFor(Topic::class, $topic);
    }

    public function update(Request $request, Topic $topic)
    {
        return $this->updateFor($request, Topic::class, $topic);
    }
}
