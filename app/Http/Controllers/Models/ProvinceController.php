<?php

namespace App\Http\Controllers\Models;

use App\Models\Province;
use Illuminate\Http\Request;

class ProvinceController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Province::class);
    }

    public function create()
    {
        return $this->createFor(Province::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Province::class);
    }

    public function edit(Province $province)
    {
        return $this->editFor(Province::class, $province);
    }

    public function update(Request $request, Province $province)
    {
        return $this->updateFor($request, Province::class, $province);
    }
}
