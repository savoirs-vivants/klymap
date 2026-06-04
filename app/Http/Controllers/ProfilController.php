<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profil\ProfileUpdateRequest;
use App\Http\Requests\Profil\PasswordUpdateRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfilController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        return view('profil.profil-edit', compact('user'));
    }

    public function update(ProfileUpdateRequest $request)
    {
        $request->user()->update($request->validated());

        return redirect()->route('profil.edit')->with('success', 'Profil mis à jour avec succès.');
    }

    public function updatePassword(PasswordUpdateRequest $request)
    {
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.'])
                ->withInput()
                ->with('tab', 'password');
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return redirect()->route('profil.edit')
            ->with('success_password', 'Mot de passe modifié avec succès.')
            ->with('tab', 'password');
    }
}
