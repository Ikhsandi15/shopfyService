<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function profile()
    {
        $data = User::find(Auth::id());

        if (!$data) {
            return Helper::APIResponse('data not found', 404, 'not found', null);
        }

        return Helper::APIResponse('success', 200, null, $data);
    }

    public function edit(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            return Helper::APIResponse('error validation', 422, $validator->errors(), null);
        }

        $user = User::find(Auth::user()->id);

        $imageName = null;

        if ($req->file('image')) {
            $imageName = time() . '_' . $req->file('image')->getClientOriginalName();
            $req->file('image')->storeAs('product', $imageName, 'public');
            $user->image = $imageName;
        }

        $user->update([
            'name' => $req->name ? $req->name : $user->name,
            'image' => $imageName
        ]);

        return Helper::APIResponse('success', 200, null, null);
    }
}
