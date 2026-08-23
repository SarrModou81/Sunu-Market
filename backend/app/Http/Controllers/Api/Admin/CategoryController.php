<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\StoreSubcategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::with('subcategories')->orderBy('position')->get();

        return response()->json(['data' => CategoryResource::collection($categories)]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create([
            ...$request->validated(),
            'slug' => Str::slug($request->string('name')),
            'position' => $request->integer('position', 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json(['data' => new CategoryResource($category)], 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return response()->json(['data' => new CategoryResource($category)]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(['message' => 'Catégorie supprimée.']);
    }

    public function storeSubcategory(StoreSubcategoryRequest $request, Category $category): JsonResponse
    {
        $subcategory = $category->subcategories()->create([
            ...$request->validated(),
            'slug' => Str::slug($request->string('name')),
            'position' => $request->integer('position', 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json(['data' => $subcategory], 201);
    }
}
