<?php

namespace App\Http\Controllers\Models;

use App\Models\Curriculum;
use Illuminate\Http\Request;

class CurriculumController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Curriculum::class);
    }

    public function create()
    {
        return $this->createFor(Curriculum::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Curriculum::class);
    }

    public function edit(Curriculum $curriculum)
    {
        return $this->editFor(Curriculum::class, $curriculum);
    }

    public function update(Request $request, Curriculum $curriculum)
    {
        return $this->updateFor($request, Curriculum::class, $curriculum);
    }
}
