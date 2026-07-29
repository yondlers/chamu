<?php

namespace App\Http\Controllers\Models;

use App\Models\Paper;
use Illuminate\Http\Request;

class PaperController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Paper::class);
    }

    public function create()
    {
        return $this->createFor(Paper::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Paper::class);
    }

    public function edit(Paper $paper)
    {
        return $this->editFor(Paper::class, $paper);
    }

    public function update(Request $request, Paper $paper)
    {
        return $this->updateFor($request, Paper::class, $paper);
    }
}
