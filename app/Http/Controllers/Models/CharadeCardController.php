<?php

namespace App\Http\Controllers\Models;

use App\Models\CharadeCard;
use Illuminate\Http\Request;

class CharadeCardController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(CharadeCard::class);
    }

    public function create()
    {
        return $this->createFor(CharadeCard::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, CharadeCard::class);
    }

    public function edit(CharadeCard $charadeCard)
    {
        return $this->editFor(CharadeCard::class, $charadeCard);
    }

    public function update(Request $request, CharadeCard $charadeCard)
    {
        return $this->updateFor($request, CharadeCard::class, $charadeCard);
    }
}
