<?php

namespace App\Http\Controllers\Models;

use App\Models\FailedJob;
use Illuminate\Http\Request;

class FailedJobController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(FailedJob::class);
    }

    public function create()
    {
        return $this->createFor(FailedJob::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, FailedJob::class);
    }

    public function edit(FailedJob $failedJob)
    {
        return $this->editFor(FailedJob::class, $failedJob);
    }

    public function update(Request $request, FailedJob $failedJob)
    {
        return $this->updateFor($request, FailedJob::class, $failedJob);
    }
}
