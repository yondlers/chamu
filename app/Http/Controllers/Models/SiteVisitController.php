<?php

namespace App\Http\Controllers\Models;

use App\Models\SiteVisit;
use Illuminate\Http\Request;

class SiteVisitController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(SiteVisit::class);
    }

    public function create()
    {
        return $this->createFor(SiteVisit::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, SiteVisit::class);
    }

    public function edit(SiteVisit $siteVisit)
    {
        return $this->editFor(SiteVisit::class, $siteVisit);
    }

    public function update(Request $request, SiteVisit $siteVisit)
    {
        return $this->updateFor($request, SiteVisit::class, $siteVisit);
    }
}
