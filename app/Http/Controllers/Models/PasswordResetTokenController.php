<?php

namespace App\Http\Controllers\Models;

use App\Models\PasswordResetToken;
use Illuminate\Http\Request;

class PasswordResetTokenController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(PasswordResetToken::class);
    }

    public function create()
    {
        return $this->createFor(PasswordResetToken::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, PasswordResetToken::class);
    }

    public function edit(PasswordResetToken $passwordResetToken)
    {
        return $this->editFor(PasswordResetToken::class, $passwordResetToken);
    }

    public function update(Request $request, PasswordResetToken $passwordResetToken)
    {
        return $this->updateFor($request, PasswordResetToken::class, $passwordResetToken);
    }
}
