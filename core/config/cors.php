<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // ✅ CORRIGÉ : Ajout de 'auth/*' pour les routes d'authentification
    'paths' => [
        'api/*',
        'auth/*',
        'sanctum/csrf-cookie'
    ],

    // ✅ Permet toutes les méthodes HTTP (GET, POST, PUT, DELETE, etc.)
    'allowed_methods' => ['*'],

    // ⚠️ IMPORTANT : En développement, '*' est OK
    // En production, remplacez par l'URL exacte de votre frontend
    // Exemple : ['https://monapp.com', 'https://www.monapp.com']
    'allowed_origins' => [
        'http://localhost:5173',  // Vite dev server
        'http://localhost:3000',  // Alternative
        'http://127.0.0.1:5173',
        'http://127.0.0.1:3000',
    ],

    'allowed_origins_patterns' => [],

    // ✅ Headers nécessaires pour Sanctum
    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'Accept',
        'Origin',
        'X-CSRF-TOKEN',
    ],

    // ✅ Headers exposés au client
    'exposed_headers' => [
        'Authorization',
    ],

    // ✅ Cache de la requête preflight (en secondes)
    'max_age' => 3600,

    // ✅ CRITIQUE : Doit être true pour Sanctum/authentification
    'supports_credentials' => true,

];
