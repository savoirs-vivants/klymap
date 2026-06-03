<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()->withErrors([
                'email' => 'Identifiants incorrects.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
