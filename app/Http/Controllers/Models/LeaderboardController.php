<?php

namespace App\Http\Controllers\Models;

use App\Models\Leaderboard;
use Illuminate\Http\Request;

class LeaderboardController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Leaderboard::class);
    }

    public function create()
    {
        return $this->createFor(Leaderboard::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Leaderboard::class);
    }

    public function edit(Leaderboard $leaderboard)
    {
        return $this->editFor(Leaderboard::class, $leaderboard);
    }

    public function update(Request $request, Leaderboard $leaderboard)
    {
        return $this->updateFor($request, Leaderboard::class, $leaderboard);
    }
}
