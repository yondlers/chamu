<?php

namespace App\Http\Controllers\Models;

use App\Models\Cache;
use Illuminate\Http\Request;

class CacheController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Cache::class);
    }

    public function create()
    {
        return $this->createFor(Cache::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Cache::class);
    }

    public function edit(Cache $cache)
    {
        return $this->editFor(Cache::class, $cache);
    }

    public function update(Request $request, Cache $cache)
    {
        return $this->updateFor($request, Cache::class, $cache);
    }
}
