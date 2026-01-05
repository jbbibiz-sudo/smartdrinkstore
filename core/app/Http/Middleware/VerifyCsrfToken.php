<?php
// Chemin: C:\smartdrinkstore\core\app\Http\Middleware\VerifyCsrfToken.php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // ✅ EXCLURE TOUTES LES ROUTES API DU CSRF
        'api/*',
        
        // ✅ Si tu as d'autres routes publiques sans authentification
        // 'webhooks/*',
    ];
}