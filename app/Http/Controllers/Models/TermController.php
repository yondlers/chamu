<?php

namespace App\Http\Controllers\Models;

use App\Models\Term;
use Illuminate\Http\Request;

class TermController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Term::class);
    }

    public function create()
    {
        return $this->createFor(Term::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Term::class);
    }

    public function edit(Term $term)
    {
        return $this->editFor(Term::class, $term);
    }

    public function update(Request $request, Term $term)
    {
        return $this->updateFor($request, Term::class, $term);
    }
}
