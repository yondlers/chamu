<?php

namespace App\Http\Controllers\Models;

use App\Models\Ad;
use Illuminate\Http\Request;

class AdController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Ad::class);
    }

    public function create()
    {
        return $this->createFor(Ad::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Ad::class);
    }

    public function edit(Ad $ad)
    {
        return $this->editFor(Ad::class, $ad);
    }

    public function update(Request $request, Ad $ad)
    {
        return $this->updateFor($request, Ad::class, $ad);
    }
}
