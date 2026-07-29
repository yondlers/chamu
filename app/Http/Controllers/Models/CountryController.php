<?php

namespace App\Http\Controllers\Models;

use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Country::class);
    }

    public function create()
    {
        return $this->createFor(Country::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Country::class);
    }

    public function edit(Country $country)
    {
        return $this->editFor(Country::class, $country);
    }

    public function update(Request $request, Country $country)
    {
        return $this->updateFor($request, Country::class, $country);
    }
}
