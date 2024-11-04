<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function register(Request $req)
    {
        $validateMsg = [
            'password.regex' => 'The :attribute must contain at least one lowercase letter, one uppercase letter, one numeric digit, and one special character'
        ];
        $validation = Validator::make($req->all(), [
            'name' => 'required',
            'email' => 'required|unique:users|email',
            'password' => [
                'required',
                'confirmed',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/'
            ],
            'password_confirmation' => 'required|same:password'
        ], $validateMsg);

        if ($validation->fails()) {
            return Helper::APIResponse('error validation', 422, $validation->errors(), null);
        }

        User::create([
            'name' => $req->name,
            'email' => $req->email,
            'password' => Hash::make($req->password)
        ]);

        return Helper::APIResponse('successful registration', 201, null, null);
    }


    public function login(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return Helper::APIResponse('error validation', 422, $validator->errors(), null);
        }

        $user = User::where('email', $req->email)->first();

        if (RateLimiter::tooManyAttempts($req->email, 5)) {
            return Helper::APIResponse('too many login attempts', 429, 'too many attemps', null);
        }

        if (!$user || !Hash::check($req->password, $user->password)) {
            RateLimiter::hit($req->email);
            return Helper::APIResponse('email or password not match', 422, 'email or password not match', null);
        }

        RateLimiter::clear($req->email);

        $user['token'] = $user->createToken('user_token')->plainTextToken;
        return Helper::APIResponse('success', 200, null, $user);
    }

    public function logout(Request $req)
    {
        try {
            if (!$req->user()) {
                return Helper::APIResponse('Unauthorized', 401, 'Invalid Token', null);
            }
            $req->user()->currentAccessToken()->delete();
            return Helper::APIResponse('logout success', 200, null, null);
        } catch (\Exception $err) {
            return Helper::APIResponse('failed logout', 500, $err->getMessage(), null);
        }
    }
}
