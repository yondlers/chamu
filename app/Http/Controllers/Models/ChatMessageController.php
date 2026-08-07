<?php

namespace App\Http\Controllers\Models;

use App\Models\ChatMessage;
use Illuminate\Http\Request;

class ChatMessageController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(ChatMessage::class);
    }

    public function create()
    {
        return $this->createFor(ChatMessage::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, ChatMessage::class);
    }

    public function edit(ChatMessage $chatMessage)
    {
        return $this->editFor(ChatMessage::class, $chatMessage);
    }

    public function update(Request $request, ChatMessage $chatMessage)
    {
        return $this->updateFor($request, ChatMessage::class, $chatMessage);
    }
}
