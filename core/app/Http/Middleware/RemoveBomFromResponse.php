<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RemoveBomFromResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Supprimer le BOM UTF-8 de toutes les reponses JSON
        $contentType = $response->headers->get('Content-Type', '');
        
        // Verifier si c'est du JSON (avec ou sans charset)
        if (str_contains($contentType, 'application/json') || str_contains($contentType, 'text/json')) {
            $content = $response->getContent();
            
            if ($content) {
                // Supprimer le BOM UTF-8 (EF BB BF en hexa)
                $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
                
                $response->setContent($content);
            }
        }

        return $response;
    }
}