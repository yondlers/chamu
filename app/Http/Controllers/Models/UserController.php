<?php

namespace App\Http\Controllers\Models;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(User::class);
    }

    public function create()
    {
        return $this->createFor(User::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, User::class);
    }

    public function edit(User $user)
    {
        return $this->editFor(User::class, $user);
    }

    public function update(Request $request, User $user)
    {
        return $this->updateFor($request, User::class, $user);
    }
}
