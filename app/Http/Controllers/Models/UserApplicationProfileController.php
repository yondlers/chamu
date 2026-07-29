<?php

namespace App\Http\Controllers\Models;

use App\Models\UserApplicationProfile;
use Illuminate\Http\Request;

class UserApplicationProfileController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(UserApplicationProfile::class);
    }

    public function create()
    {
        return $this->createFor(UserApplicationProfile::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, UserApplicationProfile::class);
    }

    public function edit(UserApplicationProfile $userApplicationProfile)
    {
        return $this->editFor(UserApplicationProfile::class, $userApplicationProfile);
    }

    public function update(Request $request, UserApplicationProfile $userApplicationProfile)
    {
        return $this->updateFor($request, UserApplicationProfile::class, $userApplicationProfile);
    }
}
