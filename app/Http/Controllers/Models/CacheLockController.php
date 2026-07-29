<?php

namespace App\Http\Controllers\Models;

use App\Models\CacheLock;
use Illuminate\Http\Request;

class CacheLockController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(CacheLock::class);
    }

    public function create()
    {
        return $this->createFor(CacheLock::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, CacheLock::class);
    }

    public function edit(CacheLock $cacheLock)
    {
        return $this->editFor(CacheLock::class, $cacheLock);
    }

    public function update(Request $request, CacheLock $cacheLock)
    {
        return $this->updateFor($request, CacheLock::class, $cacheLock);
    }
}
