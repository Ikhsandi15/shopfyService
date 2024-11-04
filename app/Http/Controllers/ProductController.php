<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    //list of products
    public function productLists(Request $req)
    {
        $products = Product::with('category')->where('is_active', true);

        if ($req->has('sort')) {
            $sortColumn = $req->input('sort');
            $sortDirection = $req->input('direction', 'asc');

            $products->orderBy($sortColumn, $sortDirection);
        }

        if ($req->has('search')) {
            $searchTerm = $req->input('search');

            $products = Product::with('category')
                ->where('slug', 'LIKE', "%{$searchTerm}%")
                ->orWhere('name', 'LIKE', "%{$searchTerm}%")
                ->orWhereHas('category', function ($query) use ($searchTerm) {
                    $query->where('slug', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('name', 'LIKE', "%{$searchTerm}%");
                });
        }

        return Helper::APIResponse('success', 200, null, $products->get());
    }

    // list of products in one category
    public function listOfProductInOneCategory(Request $req, $category_slug)
    {
        $products = Product::whereHas('category', function ($query) use ($category_slug) {
            $query->where('slug', $category_slug);
        })->with('category');

        // $products = Category::with('products')->where('slug', $category_slug);

        if (!$products->exists()) {
            return Helper::APIResponse('not found', 404, 'category not found', null);
        }

        if ($req->has('sort')) {
            $sortColumn = $req->input('sort');
            $sortDirection = $req->input('direction', 'asc');

            $products->orderBy($sortColumn, $sortDirection);
        }

        return Helper::APIResponse('success', 200, null, $products->get());

        // penggunaan get() di akhir karena untuk menggunakan orderBy() karena orderBy() tidak bisa diigunakan lagi ketika sudah di get() di eloquent
    }

    public function detail(Request $req, $product_slug)
    {
        $product = Product::with(['productImage', 'category'])->where('slug', $product_slug)->first();

        return Helper::APIResponse('success', 200, null, $product);
    }

    // create product
    public function create(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'name' => 'required',
            'slug' => 'required|max:50|unique:products,slug',
            'category_id' => 'required',
            'description' => 'nullable',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'is_active' => 'nullable|boolean',
            'image' => 'required|max:2048',
            'images' => 'required|array',
            'images.*' => 'image|max:2048' //setiap elemen dalam array gambar harus berupa file image(gambar) dan ukuran max-nya 2mb 
        ]);

        if ($validator->fails()) {
            return Helper::APIResponse('error validation', 422, $validator->errors(), null);
        }

        $product = Product::create($req->only([
            'name',
            'slug',
            'description',
            'category_id',
            'price',
            'stock',
            'image',
            'is_active'
        ]));

        if ($req->file('image')) {
            $imageName = time() . '_' . $req->file('image')->getClientOriginalName();
            $req->file('image')->storeAs('product', $imageName, 'public');
            $product->image = $imageName;
        }

        $this->storeProductImages($product, $req->file('images'));
        $product->save();

        return Helper::APIResponse('success', 200, null, $product);
    }

    protected function storeProductImages(Product $product, $images)
    {
        foreach ($images as $image) {
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->storeAs('products', $imageName, 'public');

            ProductImage::create([
                'product_id' => $product->id,
                'image' => $imageName,
            ]);
        }
    }

    // rating
    public function storeReview(Request $req, $product_slug)
    {
        $product = Product::where('slug', $product_slug)->first();

        if (!$product) {
            return Helper::APIResponse('data not found', 404, 'not found', null);
        }

        $validator = Validator::make($req->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return Helper::APIResponse('error validation', 422, $validator->errors(), null);
        }

        $review = $product->reviews()->create([
            'product_id' => $product->id,
            'user_id' => Auth::user()->id,
            'rating' => $req->rating,
            'comment' => $req->comment
        ]);

        //update raing
        $product->rating = $product->reviews()->avg('rating');
        $product->save();

        return Helper::APIResponse('success', 201, null, $review);
    }
}
