<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request)
    {
        $user = User::create([
            'firstname' => $request->firstname,
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
