<?php

namespace App\Http\Controllers\Models;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(Company::class);
    }

    public function create()
    {
        return $this->createFor(Company::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, Company::class);
    }

    public function edit(Company $company)
    {
        return $this->editFor(Company::class, $company);
    }

    public function update(Request $request, Company $company)
    {
        return $this->updateFor($request, Company::class, $company);
    }
}
