<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'redirect' => '/onboarding',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return response()->json([
                'message' => 'Credenciais inválidas.',
            ], 401);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        $companies = $user->companies->map(fn ($company) => [
            'id' => $company->id,
            'cnpj' => $company->cnpj,
            'razao_social' => $company->razao_social,
            'role' => $company->pivot->role,
        ]);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'companies' => $companies,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(null, 204);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('companies');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'companies' => $user->companies->map(fn ($company) => [
                'id' => $company->id,
                'cnpj' => $company->cnpj,
                'razao_social' => $company->razao_social,
                'role' => $company->pivot->role,
            ]),
        ]);
    }
}
