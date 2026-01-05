<?php
// Chemin: C:\smartdrinkstore\core\app\Http\Kernel.php
// ✅ VERSION CORRIGÉE - Ordre des middlewares optimisé pour CORS

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * Les middlewares globaux de l'application.
     * 
     * ⚠️ IMPORTANT: L'ordre est CRUCIAL pour CORS
     * Le CorsMiddleware doit être EN PREMIER pour intercepter
     * toutes les requêtes OPTIONS avant tout autre traitement
     */
    protected $middleware = [
        // ✅ CORS EN PREMIER - Critique pour les preflight requests
        \App\Http\Middleware\CorsMiddleware::class,
        
        // Ensuite le reste des middlewares
        \App\Http\Middleware\TrustProxies::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        
        // ⚠️ RemoveBomFromResponse EN DERNIER pour ne pas interférer
        \App\Http\Middleware\RemoveBomFromResponse::class,
    ];

    /**
     * Les groupes de middlewares de l'application.
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            // ✅ Throttle pour limiter les requêtes API
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            
            // ℹ️ Note: CORS est déjà dans $middleware global,
            // pas besoin de le répéter ici
        ],
    ];

    /**
     * Les alias de middlewares de l'application.
     */
    protected $middlewareAliases = [ 
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \App\Http\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        
        // Middlewares personnalisés
        'permission' => \App\Http\Middleware\CheckPermission::class,
        'role' => \App\Http\Middleware\CheckRole::class,
    ];

    /**
     * Priority order des middlewares.
     * 
     * ✅ CORS a la plus haute priorité
     */
    protected $middlewarePriority = [
        \App\Http\Middleware\CorsMiddleware::class,
        \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
        \Illuminate\Routing\Middleware\ThrottleRequests::class,
        \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
        \Illuminate\Contracts\Session\Middleware\AuthenticatesSessions::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ];
}
