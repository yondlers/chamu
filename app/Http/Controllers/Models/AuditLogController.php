<?php

namespace App\Http\Controllers\Models;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(AuditLog::class);
    }

    public function create()
    {
        return $this->createFor(AuditLog::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, AuditLog::class);
    }

    public function edit(AuditLog $auditLog)
    {
        return $this->editFor(AuditLog::class, $auditLog);
    }

    public function update(Request $request, AuditLog $auditLog)
    {
        return $this->updateFor($request, AuditLog::class, $auditLog);
    }
}
