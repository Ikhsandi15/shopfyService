<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function categoryLists()
    {
        $categories = Category::where('is_active', true)->get();

        return Helper::APIResponse('success', 200, null, $categories);
    }

    public function create(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'name' => 'required',
            'slug' => 'required|max:50|unique:categories,slug',
            'description' => 'nullable',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return Helper::APIResponse('error validation', 422, $validator->errors(), null);
        }

        Category::create($req->all());

        return Helper::APIResponse('success', 200, null, null);
    }

    public function update(Request $req, $category_slug)
    {
        $category = Category::where('slug', $category_slug)->first();
        if (!$category) {
            return Helper::APIResponse('not found', 404, 'category not found', null);
        }

        $validator = Validator::make($req->all(), [
            'name' => 'required',
            'slug' => 'required|max:50|unique:categories,slug,' . $category->id,
            'description' => 'nullable'
        ]);

        if ($validator->fails()) {
            return Helper::APIResponse('error validation', 422, $validator->errors(), null);
        }

        $category->update($req->all());

        return Helper::APIResponse('success', 200, null, null);
    }

    public function delete(Request $req, $category_slug)
    {
        $category = Category::where('slug', $category_slug)->first();

        if (!$category) {
            return Helper::APIResponse('not found', 404, 'category not found', null);
        }

        $category->delete();

        return Helper::APIResponse('success delete category', 200, null, null);
    }

    public function visibilityCategoryControl(Request $req, $category_slug)
    {
        $validator = Validator::make($req->all(), [
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return Helper::APIResponse('error validation', 422, $validator->errors(), null);
        }

        $category = Category::where('slug', $category_slug)->first();

        if (!$category) {
            return  Helper::APIResponse('not found', 404, 'category not found', null);
        }

        $category->is_active = $req->is_active;

        $category->update();

        return Helper::APIResponse('success', 200, null, null);
    }
}
