<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function orderHistory()
    {
        $orders = Order::where('user_id', Auth::user()->id)
            ->with('orderItems.product')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }
}
