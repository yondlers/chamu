<?php

namespace App\Http\Controllers\Models;

use App\Models\Chat;
use Illuminate\Http\Request;

class ChatController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Chat::class);
    }

    public function create()
    {
        return $this->createFor(Chat::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Chat::class);
    }

    public function edit(Chat $chat)
    {
        return $this->editFor(Chat::class, $chat);
    }

    public function update(Request $request, Chat $chat)
    {
        return $this->updateFor($request, Chat::class, $chat);
    }
}
