<?php

namespace App\Http\Controllers\Models;

use App\Models\NoteDeck;
use Illuminate\Http\Request;

class NoteDeckController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(NoteDeck::class);
    }

    public function create()
    {
        return $this->createFor(NoteDeck::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, NoteDeck::class);
    }

    public function edit(NoteDeck $noteDeck)
    {
        return $this->editFor(NoteDeck::class, $noteDeck);
    }

    public function update(Request $request, NoteDeck $noteDeck)
    {
        return $this->updateFor($request, NoteDeck::class, $noteDeck);
    }
}
