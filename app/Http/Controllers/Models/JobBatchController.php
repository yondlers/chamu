<?php

namespace App\Http\Controllers\Models;

use App\Models\JobBatch;
use Illuminate\Http\Request;

class JobBatchController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(JobBatch::class);
    }

    public function create()
    {
        return $this->createFor(JobBatch::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, JobBatch::class);
    }

    public function edit(JobBatch $jobBatch)
    {
        return $this->editFor(JobBatch::class, $jobBatch);
    }

    public function update(Request $request, JobBatch $jobBatch)
    {
        return $this->updateFor($request, JobBatch::class, $jobBatch);
    }
}
