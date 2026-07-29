<?php

namespace App\Http\Controllers\Models;

use App\Models\UserNoteDeck;
use Illuminate\Http\Request;

class UserNoteDeckController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(UserNoteDeck::class);
    }

    public function create()
    {
        return $this->createFor(UserNoteDeck::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, UserNoteDeck::class);
    }

    public function edit(UserNoteDeck $userNoteDeck)
    {
        return $this->editFor(UserNoteDeck::class, $userNoteDeck);
    }

    public function update(Request $request, UserNoteDeck $userNoteDeck)
    {
        return $this->updateFor($request, UserNoteDeck::class, $userNoteDeck);
    }
}
