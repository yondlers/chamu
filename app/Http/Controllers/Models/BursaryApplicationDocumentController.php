<?php

namespace App\Http\Controllers\Models;

use App\Models\BursaryApplicationDocument;
use Illuminate\Http\Request;

class BursaryApplicationDocumentController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(BursaryApplicationDocument::class);
    }

    public function create()
    {
        return $this->createFor(BursaryApplicationDocument::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, BursaryApplicationDocument::class);
    }

    public function edit(BursaryApplicationDocument $bursaryApplicationDocument)
    {
        return $this->editFor(BursaryApplicationDocument::class, $bursaryApplicationDocument);
    }

    public function update(Request $request, BursaryApplicationDocument $bursaryApplicationDocument)
    {
        return $this->updateFor($request, BursaryApplicationDocument::class, $bursaryApplicationDocument);
    }
}
