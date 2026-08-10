<?php

namespace App\Http\Controllers\Models;

use App\Models\Career;
use App\Support\CareerUpsert;
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

    public function store(Request $request, CareerUpsert $careerUpsert)
    {
        $name = (string) $request->input('name', '');
        $result = $careerUpsert->upsert($name, $request->only([
            'salary_expectation',
            'description',
            'source_url',
            'is_active',
        ]));

        if ($result === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Career name is not usable.'], 422);
            }

            return back()->withErrors(['name' => 'Career name is not usable.'])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json($result['career'], $result['was_created'] ? 201 : 200);
        }

        return back()->with('status', $result['was_created'] ? 'Career created.' : 'Career updated.');
    }

    public function edit(Career $career)
    {
        return $this->editFor(Career::class, $career);
    }

    public function update(Request $request, Career $career, CareerUpsert $careerUpsert)
    {
        $name = (string) $request->input('name', $career->name);
        $result = $careerUpsert->update($career, $name, $request->only([
            'salary_expectation',
            'description',
            'source_url',
            'is_active',
        ]));

        if ($result === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Career name is not usable.'], 422);
            }

            return back()->withErrors(['name' => 'Career name is not usable.'])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json($result['career']);
        }

        $status = isset($result['merged_from_id'])
            ? 'Career merged into an existing duplicate.'
            : 'Career updated.';

        return back()->with('status', $status);
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
