<?php

namespace App\Http\Controllers\Models;

use App\Models\TopicSkill;
use Illuminate\Http\Request;

class TopicSkillController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(TopicSkill::class);
    }

    public function create()
    {
        return $this->createFor(TopicSkill::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, TopicSkill::class);
    }

    public function edit(TopicSkill $topicSkill)
    {
        return $this->editFor(TopicSkill::class, $topicSkill);
    }

    public function update(Request $request, TopicSkill $topicSkill)
    {
        return $this->updateFor($request, TopicSkill::class, $topicSkill);
    }
}
