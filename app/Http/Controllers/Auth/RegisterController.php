<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'blader_name' => 'nullable|string|max:150',
            'email' => 'required|string|email|max:150|unique:users',
            'phone' => 'required|string|max:50',
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'blader_name' => $validated['blader_name'] ?? '',
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
        ]);

        AuditLogger::log('register_user', 'User', $user->id, [
            'email' => $user->email,
        ]);

        Auth::login($user);

        return redirect()->route('home');
    }
}
