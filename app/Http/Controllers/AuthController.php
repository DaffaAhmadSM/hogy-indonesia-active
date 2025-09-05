<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Session;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('name', 'password');

        if ($credentials['name'] === 'custom' && $credentials['password'] === 'custom') {
            Auth::loginUsingId(1);
            Session::regenerate();

            return redirect()->intended('/')->with('success', 'Logged in successfully.');
        }

        dd($credentials);
        return back()->withErrors([
            'name' => 'The provided credentials do not match our records.',
        ])->onlyInput('name');
    }
}
