<?php

namespace App\Http\Controllers\Models;

use App\Models\TopicContent;
use Illuminate\Http\Request;

class TopicContentController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(TopicContent::class);
    }

    public function create()
    {
        return $this->createFor(TopicContent::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, TopicContent::class);
    }

    public function edit(TopicContent $topicContent)
    {
        return $this->editFor(TopicContent::class, $topicContent);
    }

    public function update(Request $request, TopicContent $topicContent)
    {
        return $this->updateFor($request, TopicContent::class, $topicContent);
    }
}
