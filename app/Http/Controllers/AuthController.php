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
            Session::regenerate();
            Session::put('authenticated', true);
            Session::put('user', [
                'id' => 1,
                'name' => 'custom',
            ]);

            return redirect()->intended('/')->with('success', 'Logged in successfully.');
        }
        return back()->withErrors([
            'name' => 'The provided credentials do not match our records.',
        ])->onlyInput('name');
    }

    public function logout(Request $request)
    {
        Session::flush();
        Session::regenerate();

        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }
}
