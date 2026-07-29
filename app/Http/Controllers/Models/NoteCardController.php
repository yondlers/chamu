<?php

namespace App\Http\Controllers\Models;

use App\Models\NoteCard;
use Illuminate\Http\Request;

class NoteCardController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(NoteCard::class);
    }

    public function create()
    {
        return $this->createFor(NoteCard::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, NoteCard::class);
    }

    public function edit(NoteCard $noteCard)
    {
        return $this->editFor(NoteCard::class, $noteCard);
    }

    public function update(Request $request, NoteCard $noteCard)
    {
        return $this->updateFor($request, NoteCard::class, $noteCard);
    }
}
