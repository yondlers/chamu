<?php

namespace App\Http\Controllers\Models;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

abstract class ModelResourceController extends Controller
{
    protected function indexFor(string $modelClass)
    {
        /** @var Model $model */
        $model = new $modelClass;
        $query = $modelClass::query();
        $table = $model->getTable();

        if (Schema::hasColumn($table, 'created_at')) {
            $query->latest();
        } elseif (Schema::hasColumn($table, $model->getKeyName())) {
            $query->orderByDesc($model->getKeyName());
        }

        $records = $query->paginate(25)->withQueryString();
        $view = $this->viewName($modelClass, 'index');

        if (View::exists($view)) {
            return view($view, ['records' => $records, 'modelClass' => $modelClass]);
        }

        return response()->json($records);
    }

    protected function createFor(string $modelClass)
    {
        $model = new $modelClass;
        $view = $this->viewName($modelClass, 'create');

        if (View::exists($view)) {
            return view($view, ['model' => $model, 'modelClass' => $modelClass]);
        }

        return response()->json([
            'model' => $modelClass,
            'fillable' => $model->getFillable(),
        ]);
    }

    protected function storeFor(Request $request, string $modelClass): RedirectResponse|JsonResponse
    {
        /** @var Model $model */
        $model = new $modelClass;
        $model->fill($this->fillableAttributes($request, $model));
        $model->save();

        if ($request->expectsJson()) {
            return response()->json($model, 201);
        }

        return back()->with('status', class_basename($modelClass).' created.');
    }

    protected function editFor(string $modelClass, Model $model)
    {
        $view = $this->viewName($modelClass, 'edit');

        if (View::exists($view)) {
            return view($view, ['model' => $model, 'modelClass' => $modelClass]);
        }

        return response()->json($model);
    }

    protected function updateFor(Request $request, string $modelClass, Model $model): RedirectResponse|JsonResponse
    {
        $model->fill($this->fillableAttributes($request, $model));
        $model->save();

        if ($request->expectsJson()) {
            return response()->json($model);
        }

        return back()->with('status', class_basename($modelClass).' updated.');
    }

    private function fillableAttributes(Request $request, Model $model): array
    {
        $fillable = $model->getFillable();

        if ($fillable === []) {
            return [];
        }

        return $request->only($fillable);
    }

    private function viewName(string $modelClass, string $view): string
    {
        return 'models.'.Str::plural(Str::kebab(class_basename($modelClass))).'.'.$view;
    }
}
