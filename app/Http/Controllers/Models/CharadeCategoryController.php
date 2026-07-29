<?php

namespace App\Http\Controllers\Models;

use App\Models\CharadeCategory;
use Illuminate\Http\Request;

class CharadeCategoryController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(CharadeCategory::class);
    }

    public function create()
    {
        return $this->createFor(CharadeCategory::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, CharadeCategory::class);
    }

    public function edit(CharadeCategory $charadeCategory)
    {
        return $this->editFor(CharadeCategory::class, $charadeCategory);
    }

    public function update(Request $request, CharadeCategory $charadeCategory)
    {
        return $this->updateFor($request, CharadeCategory::class, $charadeCategory);
    }
}
