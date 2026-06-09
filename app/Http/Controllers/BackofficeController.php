<?php

namespace App\Http\Controllers;

use App\Http\Requests\Backoffice\UserRequest;
use App\Mail\CompteCreeMail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class BackofficeController extends Controller
{
    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->get();

        return view('backoffice.users', compact('users'));
    }

    public function store(UserRequest $request)
    {
        User::create([
            'firstname' => $request->firstname,
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
        ]);

        Mail::to($request->email)->send(
            new CompteCreeMail($request->firstname, $request->email, $request->password)
        );

        return back()->with('success', 'Utilisateur créé et e-mail envoyé.');
    }

    public function update(UserRequest $request, User $user)
    {
        $data = [
            'firstname' => $request->firstname,
            'name'      => $request->name,
            'email'     => $request->email,
            'role'      => $request->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return back()->with('success', 'Utilisateur modifié avec succès.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return back()->with('success', 'Utilisateur supprimé.');
    }
}
