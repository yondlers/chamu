<?php

namespace App\Http\Controllers\Models;

use App\Models\UserSubjectPreference;
use Illuminate\Http\Request;

class UserSubjectPreferenceController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(UserSubjectPreference::class);
    }

    public function create()
    {
        return $this->createFor(UserSubjectPreference::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, UserSubjectPreference::class);
    }

    public function edit(UserSubjectPreference $userSubjectPreference)
    {
        return $this->editFor(UserSubjectPreference::class, $userSubjectPreference);
    }

    public function update(Request $request, UserSubjectPreference $userSubjectPreference)
    {
        return $this->updateFor($request, UserSubjectPreference::class, $userSubjectPreference);
    }
}
