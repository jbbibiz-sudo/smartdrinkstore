<?php
// Chemin: C:\smartdrinkstore\core\app\Http\Controllers\Api\AuthController.php
// Contrôleur: Authentification API avec Laravel Sanctum

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    /**
     * Connexion de l'utilisateur
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Chercher l'utilisateur par email ou username
        $user = User::where('email', $request->username)
                    ->orWhere('username', $request->username)
                    ->first();

        // Vérifier si l'utilisateur existe et si le mot de passe est correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        // Vérifier si l'utilisateur est actif
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'username' => ['Votre compte a été désactivé. Contactez l\'administrateur.'],
            ]);
        }

        // Révoquer tous les anciens tokens
        $user->tokens()->delete();

        // Créer un nouveau token
        $token = $user->createToken('desktop-app')->plainTextToken;

        // Charger les relations
        $user->load(['roles.permissions']);

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'phone' => $user->phone,
                    'is_active' => $user->is_active,
                    'roles' => $user->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => $role->display_name,
                        ];
                    }),
                    'permissions' => $user->getAllPermissions()->map(function ($permission) {
                        return [
                            'name' => $permission->name,
                            'display_name' => $permission->display_name,
                            'group' => $permission->group,
                        ];
                    }),
                ],
                'token' => $token,
            ],
        ], 200);
    }

    /**
     * Déconnexion de l'utilisateur
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * ✅ Déconnexion - Invalide le token actuel
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Supprimer le token actuel de l'utilisateur
            $request->user()->currentAccessToken()->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les informations de l'utilisateur connecté
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        $user = $request->user();
        $user->load(['roles.permissions']);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'phone' => $user->phone,
                    'is_active' => $user->is_active,
                    'roles' => $user->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => $role->display_name,
                        ];
                    }),
                    'permissions' => $user->getAllPermissions()->map(function ($permission) {
                        return [
                            'name' => $permission->name,
                            'display_name' => $permission->display_name,
                            'group' => $permission->group,
                        ];
                    }),
                ],
            ],
        ], 200);
    }

    /**
     * Vérifier la session (token valide)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkSession(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Session valide',
            'data' => [
                'authenticated' => true,
            ],
        ], 200);
    }

    /**
     * Changer le mot de passe
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        // Vérifier l'ancien mot de passe
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        // Mettre à jour le mot de passe
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Révoquer tous les tokens sauf le token actuel
        $currentTokenId = $request->user()->currentAccessToken()->id;
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe changé avec succès',
        ], 200);
    }
}
