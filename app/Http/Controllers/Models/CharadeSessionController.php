<?php

namespace App\Http\Controllers\Models;

use App\Models\CharadeSession;
use Illuminate\Http\Request;

class CharadeSessionController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(CharadeSession::class);
    }

    public function create()
    {
        return $this->createFor(CharadeSession::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, CharadeSession::class);
    }

    public function edit(CharadeSession $charadeSession)
    {
        return $this->editFor(CharadeSession::class, $charadeSession);
    }

    public function update(Request $request, CharadeSession $charadeSession)
    {
        return $this->updateFor($request, CharadeSession::class, $charadeSession);
    }
}
