<?php
// Chemin: C:\smartdrinkstore\core\app\Console\Commands\TestCors.php
// Commande artisan pour diagnostiquer CORS
// Usage: php artisan cors:test

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestCors extends Command
{
    protected $signature = 'cors:test {--url=http://localhost:8000/api/auth/login}';
    protected $description = 'Test CORS configuration';

    public function handle()
    {
        $url = $this->option('url');
        $origin = 'http://localhost:5173';

        $this->info('🧪 Testing CORS Configuration');
        $this->line('');
        $this->info("📍 URL: $url");
        $this->info("🌐 Origin: $origin");
        $this->line('');

        // ============================================
        // Test 1: OPTIONS Request (Preflight)
        // ============================================
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📋 Test 1: OPTIONS Preflight Request');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        try {
            $response = Http::withHeaders([
                'Origin' => $origin,
                'Access-Control-Request-Method' => 'POST',
                'Access-Control-Request-Headers' => 'Content-Type, Authorization',
            ])->withOptions(['allow_redirects' => false])
              ->send('OPTIONS', $url);

            $this->displayResponse($response, 'OPTIONS');
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }

        $this->line('');

        // ============================================
        // Test 2: POST Request
        // ============================================
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📋 Test 2: POST Request with Origin');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        try {
            $response = Http::withHeaders([
                'Origin' => $origin,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($url, [
                'username' => 'admin',
                'password' => 'admin123',
            ]);

            $this->displayResponse($response, 'POST');
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }

        $this->line('');

        // ============================================
        // Test 3: Middleware Check
        // ============================================
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📋 Test 3: Middleware Configuration');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $middlewares = $this->getGlobalMiddlewares();

        $corsMiddlewareFound = false;
        $corsPosition = 0;

        foreach ($middlewares as $index => $middleware) {
            if (str_contains($middleware, 'CorsMiddleware')) {
                $corsMiddlewareFound = true;
                $corsPosition = $index + 1;
                $this->info("✅ CorsMiddleware found at position $corsPosition");
            }
        }

        if (!$corsMiddlewareFound) {
            $this->error('❌ CorsMiddleware NOT found in global middlewares');
        } elseif ($corsPosition > 1) {
            $this->warn("⚠️  CorsMiddleware is at position $corsPosition, should be position 1");
        } else {
            $this->info('✅ CorsMiddleware is correctly positioned');
        }

        $this->line('');

        // ============================================
        // Test 4: Log Check
        // ============================================
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📋 Test 4: Recent CORS Logs');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $this->info("Reading last 20 CORS-related log entries...");
            $logs = file($logFile);
            $corsLogs = array_filter($logs, function($line) {
                return str_contains($line, 'CORS') || str_contains($line, 'Origin');
            });
            
            $recentLogs = array_slice($corsLogs, -20);
            
            if (empty($recentLogs)) {
                $this->warn('⚠️  No CORS logs found. Middleware might not be executing.');
            } else {
                foreach ($recentLogs as $log) {
                    $this->line($log);
                }
            }
        } else {
            $this->warn('⚠️  Log file not found');
        }

        $this->line('');

        // ============================================
        // Summary
        // ============================================
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 SUMMARY');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line('If all tests pass, CORS should work in Electron app.');
        $this->line('If tests fail, check:');
        $this->line('  1. Middleware is enabled and first in Kernel.php');
        $this->line('  2. Allowed origins include http://localhost:5173');
        $this->line('  3. No conflicting CORS packages installed');
        $this->line('');

        return 0;
    }

    private function displayResponse($response, $method)
    {
        $this->info("Status: " . $response->status());
        
        $corsHeaders = [
            'Access-Control-Allow-Origin',
            'Access-Control-Allow-Methods',
            'Access-Control-Allow-Headers',
            'Access-Control-Allow-Credentials',
            'Access-Control-Max-Age',
        ];

        $this->line('');
        $this->info('CORS Headers:');
        
        $foundHeaders = 0;
        foreach ($corsHeaders as $header) {
            if ($response->hasHeader($header)) {
                $this->info("  ✅ $header: " . $response->header($header));
                $foundHeaders++;
            } else {
                $this->error("  ❌ $header: NOT PRESENT");
            }
        }

        if ($foundHeaders === 0) {
            $this->error('⚠️  NO CORS headers found in response!');
        } elseif ($foundHeaders < count($corsHeaders)) {
            $this->warn("⚠️  Only $foundHeaders/" . count($corsHeaders) . " CORS headers present");
        } else {
            $this->info('✅ All CORS headers present');
        }
    }

    private function getGlobalMiddlewares()
    {
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        
        // Use reflection to access protected property
        $reflection = new \ReflectionClass($kernel);
        $property = $reflection->getProperty('middleware');
        $property->setAccessible(true);
        
        return $property->getValue($kernel);
    }
}
