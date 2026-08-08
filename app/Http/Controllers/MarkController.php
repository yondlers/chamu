<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MarkController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('subjects.index', array_filter([
            'manage' => 1,
            'term_id' => $request->integer('term_id') ?: null,
        ]));
    }

    public function update(Request $request)
    {
        return redirect()
            ->route('subjects.index', ['manage' => 1])
            ->with('status', 'Marks are now saved with subjects on one screen.');
    }
}
