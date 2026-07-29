<?php

namespace App\Http\Controllers\Models;

use App\Models\UserType;
use Illuminate\Http\Request;

class UserTypeController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(UserType::class);
    }

    public function create()
    {
        return $this->createFor(UserType::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, UserType::class);
    }

    public function edit(UserType $userType)
    {
        return $this->editFor(UserType::class, $userType);
    }

    public function update(Request $request, UserType $userType)
    {
        return $this->updateFor($request, UserType::class, $userType);
    }
}
