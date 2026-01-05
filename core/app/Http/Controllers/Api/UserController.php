<?php
// Chemin: app/Http/Controllers/Api/UserController.php

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
        // ✅ CORRECTION: Vérifier avec hasRole au lieu de isAdmin
        if (!$request->user()->hasRole('admin') && !$request->user()->hasPermission('manage_users')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé',
            ], 403);
        }

        $query = User::query();

        // Filtrer par rôle si demandé
        if ($request->has('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
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

        // Charger la relation roles
        $users = $query->with('roles')->orderBy('name')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
                'role_label' => $user->roles->first()?->display_name ?? 'Aucun',
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
        // ✅ CORRECTION: hasRole au lieu de isAdmin
        if (!$request->user()->hasRole('admin') && !$request->user()->hasPermission('manage_users')) {
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

        // ✅ GÉNÉRATION AUTOMATIQUE DU USERNAME
        $username = $this->generateUsername($validated['email'], $validated['name']);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $username,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Assigner le rôle via la relation
        $user->assignRole($validated['role']);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
                'role_label' => $user->roles->first()?->display_name,
                'is_active' => $user->is_active,
            ],
        ], 201);
    }

/**
     * ✅ NOUVELLE MÉTHODE : Générer un username unique
     */
    private function generateUsername($email, $name)
    {
        // Extraire la partie avant @ de l'email
        $baseUsername = strtolower(explode('@', $email)[0]);
        
        // Nettoyer (enlever caractères spéciaux)
        $baseUsername = preg_replace('/[^a-z0-9_]/', '', $baseUsername);
        
        // Vérifier si le username existe déjà 
        $username = $baseUsername;
        $counter = 1;
        
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }

    /**
     * Afficher un utilisateur
     */
    public function show(Request $request, $id)
    {
        // ✅ CORRECTION: hasRole au lieu de isAdmin
        if (!$request->user()->hasRole('admin') && !$request->user()->hasPermission('manage_users')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé',
            ], 403);
        }

        $user = User::with('roles')->findOrFail($id);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roles->first()?->name,
            'role_label' => $user->roles->first()?->display_name,
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
        // ✅ CORRECTION: hasRole au lieu de isAdmin
        if (!$request->user()->hasRole('admin') && !$request->user()->hasPermission('manage_users')) {
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

        // ✅ Si l'email change, régénérer le username
        if (isset($validated['email']) && $validated['email'] !== $user->email) {
            $validated['username'] = $this->generateUsername($validated['email'], $validated['name'] ?? $user->name);
        }

        // Mise à jour du mot de passe si fourni
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        // Mettre à jour le rôle si fourni
        if (isset($validated['role'])) {
            // Empêcher un utilisateur de changer son propre rôle.
            if ($user->id === $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas modifier votre propre rôle.',
                ], 403);
            }
            $user->syncRoles([$validated['role']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
                'role_label' => $user->roles->first()?->display_name,
                'is_active' => $user->is_active,
            ],
        ]);
    }

    /**
     * Supprimer un utilisateur (soft delete)
     */
    public function destroy(Request $request, $id)
    {
        // ✅ CORRECTION: hasRole au lieu de isAdmin
        if (!$request->user()->hasRole('admin') && !$request->user()->hasPermission('manage_users')) {
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

        // Empêcher la suppression d'un administrateur
        if ($user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'La suppression d\'un compte administrateur n\'est pas autorisée pour des raisons de sécurité.',
            ], 403);
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
        // ✅ CORRECTION: hasRole au lieu de isAdmin
        if (!$request->user()->hasRole('admin') && !$request->user()->hasPermission('manage_users')) {
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

        // Empêcher la désactivation du dernier administrateur actif
        if ($user->hasRole('admin') && $user->is_active) {
            $activeAdminCount = User::where('is_active', true)->whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->count();

            if ($activeAdminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de désactiver le dernier administrateur actif du système.',
                ], 403);
            }
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
        // ✅ CORRECTION: hasRole au lieu de isAdmin
        if (!$request->user()->hasRole('admin') && !$request->user()->hasPermission('manage_users')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé',
            ], 403);
        }

        // ✅ CORRECTION: Utiliser whereHas pour compter par rôle
        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'admins' => User::whereHas('roles', function($q) {
                $q->where('name', 'admin');
            })->count(),
            'managers' => User::whereHas('roles', function($q) {
                $q->where('name', 'manager');
            })->count(),
            'cashiers' => User::whereHas('roles', function($q) {
                $q->where('name', 'cashier');
            })->count(),
        ];

        return response()->json($stats);
    }
}