<?php

namespace App\Http\Controllers\Api;

use App\Enums\CompanyRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = Company::create($request->validated());

        $request->user()->companies()->attach($company->id, [
            'role' => CompanyRole::ADMIN->value,
        ]);

        $request->session()->put('company_id', $company->id);

        return response()->json($company, 201);
    }

    public function show(Company $company, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->companies()->where('companies.id', $company->id)->exists()) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return response()->json($company);
    }

    public function update(StoreCompanyRequest $request, Company $company): JsonResponse
    {
        $user = $request->user();

        if (! $user->companies()->where('companies.id', $company->id)->exists()) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $company->update($request->validated());

        return response()->json($company);
    }

    public function select(Company $company, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->companies()->where('companies.id', $company->id)->exists()) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $request->session()->put('company_id', $company->id);

        return response()->json([
            'message' => 'Empresa selecionada',
            'company_id' => $company->id,
        ]);
    }

    public function members(Company $company, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->companies()->where('companies.id', $company->id)->exists()) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $members = $company->users->map(fn ($u) => [
            'user_id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->pivot->role,
        ]);

        return response()->json($members);
    }

    public function addMember(Company $company, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'string', 'in:admin,contador,operador'],
        ]);

        $member = User::where('email', $validated['email'])->first();

        if (! $member) {
            return response()->json(['message' => 'Usuário não encontrado com este e-mail.'], 404);
        }

        if ($company->users()->where('users.id', $member->id)->exists()) {
            return response()->json(['message' => 'Usuário já é membro desta empresa.'], 409);
        }

        $company->users()->attach($member->id, ['role' => $validated['role']]);

        return response()->json([
            'user_id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'role' => $validated['role'],
        ], 201);
    }

    public function updateMemberRole(Company $company, int $userId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:admin,contador,operador'],
        ]);

        if (! $company->users()->where('users.id', $userId)->exists()) {
            return response()->json(['message' => 'Membro não encontrado.'], 404);
        }

        $company->users()->updateExistingPivot($userId, ['role' => $validated['role']]);

        return response()->json(['message' => 'Permissão atualizada.']);
    }

    public function removeMember(Company $company, int $userId, Request $request): JsonResponse
    {
        if ($request->user()->id === $userId) {
            return response()->json(['message' => 'Você não pode remover a si mesmo.'], 422);
        }

        if (! $company->users()->where('users.id', $userId)->exists()) {
            return response()->json(['message' => 'Membro não encontrado.'], 404);
        }

        $company->users()->detach($userId);

        return response()->json(null, 204);
    }
}
