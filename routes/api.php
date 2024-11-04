<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;

Route::prefix('/v1')->group(function () {
    Route::prefix('/auth')->controller(AuthController::class)->group(function () {
        Route::post('/register', 'register');
        Route::post('/login', 'login');
        Route::post('/logout', 'logout')->middleware('auth:sanctum');
    });

    Route::prefix('/users')->controller(UserController::class)->middleware('auth:sanctum')->group(function() {
        Route::get('/','profile');
        Route::put('/','edit');
    });

    Route::prefix('/products')->controller(ProductController::class)->group(function () {
        Route::get('/', 'productLists');
        Route::get('/detail/{product_slug}', 'detail');
        Route::get('/{category_slug}', 'listOfProductInOneCategory');
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', 'create')->middleware('admin');
            Route::post('/review/{product_slug}', 'storeReview');
        });
    });

    Route::prefix('/categories')->controller(CategoryController::class)->group(function () {
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/', 'categoryLists');
            Route::post('/', 'create')->middleware('admin');
            Route::put('/update/{category_slug}', 'update')->middleware('admin');
            Route::put('/visibility-update/{category_slug}', 'visibilityCategoryControl')->middleware('admin');
            Route::delete('/delete/{category_id}', 'delete')->middleware('admin');
        });
    });

    Route::prefix('/cart')->controller(CartController::class)->group(function () {
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/', 'myCart');
            Route::post('/add', 'addToCart');
            Route::put('/update', 'updateCart');
            Route::post('/checkout', 'checkout');
        });
    });

    Route::get('/order/history', [OrderController::class, 'orderHistory'])->middleware('auth:sanctum');
});
