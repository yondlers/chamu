<?php

namespace App\Http\Controllers\Models;

use App\Models\BursaryApplication;
use Illuminate\Http\Request;

class BursaryApplicationController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(BursaryApplication::class);
    }

    public function create()
    {
        return $this->createFor(BursaryApplication::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, BursaryApplication::class);
    }

    public function edit(BursaryApplication $bursaryApplication)
    {
        return $this->editFor(BursaryApplication::class, $bursaryApplication);
    }

    public function update(Request $request, BursaryApplication $bursaryApplication)
    {
        return $this->updateFor($request, BursaryApplication::class, $bursaryApplication);
    }
}
