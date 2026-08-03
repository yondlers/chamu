<?php

namespace App\Http\Controllers\Models;

use App\Models\CareerQualification;
use Illuminate\Http\Request;

class CareerQualificationController extends ModelResourceController
{
    public function index()
    {
        return $this->indexFor(CareerQualification::class);
    }

    public function create()
    {
        return $this->createFor(CareerQualification::class);
    }

    public function store(Request $request)
    {
        return $this->storeFor($request, CareerQualification::class);
    }

    public function edit(CareerQualification $careerQualification)
    {
        return $this->editFor(CareerQualification::class, $careerQualification);
    }

    public function update(Request $request, CareerQualification $careerQualification)
    {
        return $this->updateFor($request, CareerQualification::class, $careerQualification);
    }

    public function delete(Request $request, CareerQualification $careerQualification)
    {
        return $this->destroy($request, $careerQualification);
    }

    public function destroy(Request $request, CareerQualification $careerQualification)
    {
        $careerQualification->delete();

        if ($request->expectsJson()) {
            return response()->json(['deleted' => true]);
        }

        return back()->with('status', 'CareerQualification deleted.');
    }
}
