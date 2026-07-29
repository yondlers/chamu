<?php

namespace App\Http\Controllers\Models;

use App\Models\NqfLevel;
use Illuminate\Http\Request;

class NqfLevelController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(NqfLevel::class);
    }

    public function create()
    {
        return $this->createFor(NqfLevel::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, NqfLevel::class);
    }

    public function edit(NqfLevel $nqfLevel)
    {
        return $this->editFor(NqfLevel::class, $nqfLevel);
    }

    public function update(Request $request, NqfLevel $nqfLevel)
    {
        return $this->updateFor($request, NqfLevel::class, $nqfLevel);
    }
}
