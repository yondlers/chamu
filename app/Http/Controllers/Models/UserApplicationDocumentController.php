<?php

namespace App\Http\Controllers\Models;

use App\Models\UserApplicationDocument;
use Illuminate\Http\Request;

class UserApplicationDocumentController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(UserApplicationDocument::class);
    }

    public function create()
    {
        return $this->createFor(UserApplicationDocument::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, UserApplicationDocument::class);
    }

    public function edit(UserApplicationDocument $userApplicationDocument)
    {
        return $this->editFor(UserApplicationDocument::class, $userApplicationDocument);
    }

    public function update(Request $request, UserApplicationDocument $userApplicationDocument)
    {
        return $this->updateFor($request, UserApplicationDocument::class, $userApplicationDocument);
    }
}
