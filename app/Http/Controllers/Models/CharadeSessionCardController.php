<?php

namespace App\Http\Controllers\Models;

use App\Models\CharadeSessionCard;
use Illuminate\Http\Request;

class CharadeSessionCardController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(CharadeSessionCard::class);
    }

    public function create()
    {
        return $this->createFor(CharadeSessionCard::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, CharadeSessionCard::class);
    }

    public function edit(CharadeSessionCard $charadeSessionCard)
    {
        return $this->editFor(CharadeSessionCard::class, $charadeSessionCard);
    }

    public function update(Request $request, CharadeSessionCard $charadeSessionCard)
    {
        return $this->updateFor($request, CharadeSessionCard::class, $charadeSessionCard);
    }
}
