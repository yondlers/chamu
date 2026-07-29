<?php

namespace App\Http\Controllers\Models;

use App\Models\Job;
use Illuminate\Http\Request;

class JobController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Job::class);
    }

    public function create()
    {
        return $this->createFor(Job::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Job::class);
    }

    public function edit(Job $job)
    {
        return $this->editFor(Job::class, $job);
    }

    public function update(Request $request, Job $job)
    {
        return $this->updateFor($request, Job::class, $job);
    }
}
