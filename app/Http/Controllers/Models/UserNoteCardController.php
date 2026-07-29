<?php

namespace App\Http\Controllers\Models;

use App\Models\UserNoteCard;
use Illuminate\Http\Request;

class UserNoteCardController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(UserNoteCard::class);
    }

    public function create()
    {
        return $this->createFor(UserNoteCard::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, UserNoteCard::class);
    }

    public function edit(UserNoteCard $userNoteCard)
    {
        return $this->editFor(UserNoteCard::class, $userNoteCard);
    }

    public function update(Request $request, UserNoteCard $userNoteCard)
    {
        return $this->updateFor($request, UserNoteCard::class, $userNoteCard);
    }
}
