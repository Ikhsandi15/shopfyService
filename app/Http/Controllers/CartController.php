<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Helpers\Helper;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function myCart()
    {
        $datas = Cart::where('user_id', Auth::user()->id)->get();

        return Helper::APIResponse('success', 200, null, $datas);
    }

    public function addToCart(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return Helper::APIResponse('error validation', 422, $validator->errors(), null);
        }

        $productId = $req->product_id;
        $userId = Auth::user()->id;
        $qty = $req->qty;

        DB::transaction(function () use ($productId, $userId, $qty) {
            $product = Product::lockForUpdate()->find($productId); // Mengunci baris produk untuk menghindari race condition

            if (!$product) {
                throw new \Exception('Product not found');
            }

            if ($product->stock < $qty) {
                throw new \Exception('Insufficient stock available');
            }

            $cart = Cart::where('user_id', $userId)
                ->where('product_id', $productId)
                ->lockForUpdate() // kunci baris untuk menghindari race condition
                ->first();

            if ($cart) {
                $newQty = $cart->qty + $qty;

                if ($newQty > $product->stock) {
                    throw new \Exception('Insufficient stock available');
                }

                $cart->qty = $newQty;
                $cart->save();
            } else {
                if ($qty > $product->stock) {
                    throw new \Exception('Insufficient stock available');
                }

                Cart::create([
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'qty' => $qty
                ]);
            }
        });

        return Helper::APIResponse('product added to cart successfully', 200, null, null);
    }

    public function checkout(Request $req)
    {
        $userId = Auth::user()->id;

        DB::transaction(function () use ($userId) {
            $cartItems = Cart::where('user_id', $userId)->with('product')->lockForUpdate()->get();

            if ($cartItems->isEmpty()) {
                throw new \Exception('Cart is empty');
            }

            $totalPrice = $cartItems->sum(function ($cartItem) {
                return $cartItem->product->price * $cartItem->qty;
            });

            $order = Order::create([
                'user_id' => $userId,
                'total_price' => $totalPrice,
                'status' => 'success',
            ]);

            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;
                $product->stock -= $cartItem->qty;
                $product->save();
    
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->qty,
                    'price' => $cartItem->product->price,
                ]);
            }

            Cart::where('user_id', $userId)->delete();
        });

        return Helper::APIResponse('checkout successful', 200, null, null);
    }

    public function updateCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
            'action' => 'required|in:add,subtract'
        ]);

        if ($validator->fails()) {
            return Helper::APIResponse('error validation', 422, $validator->errors(), null);
        }

        $productId = $request->product_id;
        $userId = Auth::user()->id;
        $qty = $request->qty;
        $action = $request->action;

        DB::transaction(function () use ($productId, $userId, $qty, $action) {
            $product = Product::lockForUpdate()->find($productId);

            if (!$product) {
                throw new \Exception('Product not found');
            }

            $cart = Cart::where('user_id', $userId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$cart) {
                if ($action === 'subtract') {
                    throw new \Exception('Cannot subtract from a non-existent cart item');
                }

                if ($qty > $product->stock) {
                    throw new \Exception('Insufficient stock available');
                }

                Cart::create([
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'qty' => $qty
                ]);
            } else {
                if ($action === 'add') {
                    $newQty = $cart->qty + $qty;

                    if ($newQty > $product->stock) {
                        throw new \Exception('Insufficient stock available');
                    }

                    $cart->qty = $newQty;
                } elseif ($action === 'subtract') {
                    $newQty = $cart->qty - $qty;

                    if ($newQty < 0) {
                        throw new \Exception('Cannot have negative quantity in cart');
                    }

                    if ($newQty == 0) {
                        $cart->delete();
                        return;
                    }

                    $cart->qty = $newQty;
                }

                $cart->save();
            }
        });

        return Helper::APIResponse('Cart updated successfully', 200, null, null);
    }
}
