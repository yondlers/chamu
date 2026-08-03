<?php

namespace App\Http\Controllers\Models;

use App\Models\Career;
use Illuminate\Http\Request;

class CareerController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Career::class);
    }

    public function create()
    {
        return $this->createFor(Career::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Career::class);
    }

    public function edit(Career $career)
    {
        return $this->editFor(Career::class, $career);
    }

    public function update(Request $request, Career $career)
    {
        return $this->updateFor($request, Career::class, $career);
    }

    public function delete(Request $request, Career $career)
    {
        return $this->destroy($request, $career);
    }

    public function destroy(Request $request, Career $career)
    {
        $career->delete();

        if ($request->expectsJson()) {
            return response()->json(['deleted' => true]);
        }

        return back()->with('status', 'Career deleted.');
    }
}
