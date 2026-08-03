<?php

namespace App\Http\Controllers\Models;

use App\Models\EmailLog;
use Illuminate\Http\Request;

class EmailLogController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(EmailLog::class);
    }

    public function create()
    {
        return $this->createFor(EmailLog::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, EmailLog::class);
    }

    public function edit(EmailLog $emailLog)
    {
        return $this->editFor(EmailLog::class, $emailLog);
    }

    public function update(Request $request, EmailLog $emailLog)
    {
        return $this->updateFor($request, EmailLog::class, $emailLog);
    }

    public function delete(Request $request, EmailLog $emailLog)
    {
        return $this->destroy($request, $emailLog);
    }

    public function destroy(Request $request, EmailLog $emailLog)
    {
        $emailLog->delete();

        if ($request->expectsJson()) {
            return response()->json(['deleted' => true]);
        }

        return back()->with('status', 'EmailLog deleted.');
    }
}
