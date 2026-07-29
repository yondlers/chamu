<?php

namespace App\Http\Controllers\Models;

use App\Models\Bursary;
use Illuminate\Http\Request;

class BursaryController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Bursary::class);
    }

    public function create()
    {
        return $this->createFor(Bursary::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Bursary::class);
    }

    public function edit(Bursary $bursary)
    {
        return $this->editFor(Bursary::class, $bursary);
    }

    public function update(Request $request, Bursary $bursary)
    {
        return $this->updateFor($request, Bursary::class, $bursary);
    }
}
