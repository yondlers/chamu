<?php

namespace App\Http\Controllers\Models;

use App\Models\Session;
use Illuminate\Http\Request;

class SessionController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Session::class);
    }

    public function create()
    {
        return $this->createFor(Session::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Session::class);
    }

    public function edit(Session $session)
    {
        return $this->editFor(Session::class, $session);
    }

    public function update(Request $request, Session $session)
    {
        return $this->updateFor($request, Session::class, $session);
    }
}
