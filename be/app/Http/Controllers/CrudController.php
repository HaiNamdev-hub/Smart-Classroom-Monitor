<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class CrudController extends Controller
{
    /** @var class-string<Model> */
    protected string $modelClass;

    abstract protected function rules(?Model $record = null): array;

    public function index(): mixed
    {
        return $this->modelClass::query()->paginate();
    }

    public function store(Request $request): JsonResponse
    {
        $record = $this->modelClass::query()->create($request->validate($this->rules()));

        return response()->json($record, 201);
    }

    public function show(int $id): Model
    {
        return $this->modelClass::query()->findOrFail($id);
    }

    public function update(Request $request, int $id): Model
    {
        $record = $this->modelClass::query()->findOrFail($id);
        $record->update($request->validate($this->rules($record)));

        return $record->refresh();
    }

    public function destroy(int $id): JsonResponse
    {
        $this->modelClass::query()->findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
