<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Liste de tous les utilisateurs
     */
    public function index(Request $request)
    {
        // Vérifier les permissions
        if (!$request->user()->hasPermission('manage_users')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé',
            ], 403);
        }

        $query = User::query();

        // Filtrer par rôle si demandé
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filtrer par statut si demandé
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Recherche par nom ou email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'role_label' => $user->role_label,
                'is_active' => $user->is_active,
                'phone' => $user->phone,
                'address' => $user->address,
                'last_login_at' => $user->last_login_at,
                'created_at' => $user->created_at,
            ];
        });

        return response()->json($users);
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        // Vérifier les permissions
        if (!$request->user()->hasPermission('manage_users')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,manager,cashier',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'role_label' => $user->role_label,
                'is_active' => $user->is_active,
            ],
        ], 201);
    }

    /**
     * Afficher un utilisateur
     */
    public function show(Request $request, $id)
    {
        // Vérifier les permissions
        if (!$request->user()->hasPermission('manage_users')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé',
            ], 403);
        }

        $user = User::findOrFail($id);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_label' => $user->role_label,
            'is_active' => $user->is_active,
            'phone' => $user->phone,
            'address' => $user->address,
            'last_login_at' => $user->last_login_at,
            'created_at' => $user->created_at,
        ]);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, $id)
    {
        // Vérifier les permissions
        if (!$request->user()->hasPermission('manage_users')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé',
            ], 403);
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => 'sometimes|nullable|string|min:6',
            'role' => 'sometimes|required|in:admin,manager,cashier',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Empêcher un utilisateur de se désactiver lui-même
        if (isset($validated['is_active']) && !$validated['is_active'] && $user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas désactiver votre propre compte',
            ], 400);
        }

        // Mise à jour du mot de passe si fourni
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'role_label' => $user->role_label,
                'is_active' => $user->is_active,
            ],
        ]);
    }

    /**
     * Supprimer un utilisateur (soft delete)
     */
    public function destroy(Request $request, $id)
    {
        // Vérifier les permissions
        if (!$request->user()->hasPermission('manage_users')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé',
            ], 403);
        }

        $user = User::findOrFail($id);

        // Empêcher un utilisateur de se supprimer lui-même
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte',
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès',
        ]);
    }

    /**
     * Activer/Désactiver un utilisateur
     */
    public function toggleActive(Request $request, $id)
    {
        // Vérifier les permissions
        if (!$request->user()->hasPermission('manage_users')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé',
            ], 403);
        }

        $user = User::findOrFail($id);

        // Empêcher un utilisateur de se désactiver lui-même
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas modifier votre propre statut',
            ], 400);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'success' => true,
            'message' => $user->is_active ? 'Utilisateur activé' : 'Utilisateur désactivé',
            'user' => [
                'id' => $user->id,
                'is_active' => $user->is_active,
            ],
        ]);
    }

    /**
     * Statistiques des utilisateurs
     */
    public function stats(Request $request)
    {
        // Vérifier les permissions
        if (!$request->user()->hasPermission('manage_users')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé',
            ], 403);
        }

        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'admins' => User::where('role', 'admin')->count(),
            'managers' => User::where('role', 'manager')->count(),
            'cashiers' => User::where('role', 'cashier')->count(),
        ];

        return response()->json($stats);
    }
}
