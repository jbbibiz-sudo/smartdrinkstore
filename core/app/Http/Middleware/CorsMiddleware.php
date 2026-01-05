<?php
// Chemin: C:\smartdrinkstore\core\app\Http\Middleware\CorsMiddleware.php
// ✅ VERSION AMÉLIORÉE avec logging détaillé

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ============================================
        // 📋 LOGGING POUR DEBUG
        // ============================================
        Log::info('🔍 CORS Middleware - Request', [
            'method' => $request->getMethod(),
            'url' => $request->fullUrl(),
            'origin' => $request->header('Origin'),
            'has_origin' => $request->hasHeader('Origin'),
        ]);

        // ============================================
        // 🌐 ORIGINES AUTORISÉES
        // ============================================
        $allowedOrigins = [
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'http://localhost:8000',
            'http://127.0.0.1:8000',
            'http://localhost:3000',  // Si autre frontend
        ];

        $origin = $request->header('Origin');
        $isAllowedOrigin = $origin && in_array($origin, $allowedOrigins);

        Log::info('🔍 Origin Check', [
            'origin' => $origin,
            'is_allowed' => $isAllowedOrigin,
            'allowed_origins' => $allowedOrigins,
        ]);

        // ============================================
        // ✅ PREFLIGHT REQUEST (OPTIONS)
        // ============================================
        if ($request->getMethod() === 'OPTIONS') {
            Log::info('⚡ OPTIONS Preflight Request détecté');
            
            $response = response()->json([
                'status' => 'OK',
                'message' => 'CORS Preflight OK'
            ], 200);
            
            // Toujours ajouter les headers CORS pour OPTIONS
            $response->headers->set('Access-Control-Allow-Origin', $origin ?: '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With, X-CSRF-TOKEN, Origin');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '86400');
            
            Log::info('✅ OPTIONS Response Headers', [
                'allow_origin' => $response->headers->get('Access-Control-Allow-Origin'),
                'allow_methods' => $response->headers->get('Access-Control-Allow-Methods'),
                'allow_credentials' => $response->headers->get('Access-Control-Allow-Credentials'),
            ]);
            
            return $response;
        }

        // ============================================
        // 🔄 REQUÊTE NORMALE (GET, POST, etc.)
        // ============================================
        Log::info('🔄 Processing normal request');
        
        // Laisser la requête continuer
        $response = $next($request);

        Log::info('📤 Response before CORS headers', [
            'status' => $response->getStatusCode(),
            'has_cors' => $response->headers->has('Access-Control-Allow-Origin'),
        ]);

        // ============================================
        // ✅ AJOUTER LES HEADERS CORS À LA RÉPONSE
        // ============================================
        if ($isAllowedOrigin || !$origin) {
            // Utiliser l'origin de la requête si autorisée, sinon '*'
            $corsOrigin = $isAllowedOrigin ? $origin : '*';
            
            $response->headers->set('Access-Control-Allow-Origin', $corsOrigin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With, X-CSRF-TOKEN, Origin');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Expose-Headers', 'Content-Length, X-JSON');
            
            Log::info('✅ CORS Headers Added', [
                'origin' => $corsOrigin,
                'credentials' => 'true',
            ]);
        } else {
            Log::warning('⚠️ Origin not allowed, no CORS headers added', [
                'origin' => $origin,
            ]);
        }

        Log::info('📥 Final Response', [
            'status' => $response->getStatusCode(),
            'cors_origin' => $response->headers->get('Access-Control-Allow-Origin'),
            'cors_credentials' => $response->headers->get('Access-Control-Allow-Credentials'),
        ]);

        return $response;
    }
}
