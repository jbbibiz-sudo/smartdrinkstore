<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DesktopController extends Controller
{
    /**
     * Obtenir les statistiques de l'application desktop
     */
    public function getStats()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'app_mode' => env('APP_MODE', 'web'),
                'database' => $this->getDatabaseStats(),
                'storage' => $this->getStorageInfo(),
                'timestamp' => now()
            ]
        ]);
    }
    
    /**
     * Créer une sauvegarde
     */
    public function createBackup(Request $request)
    {
        try {
            $databasePath = database_path('database.sqlite');
            $backupPath = storage_path('backups/backup_' . date('Y-m-d_His') . '.sqlite');
            
            // Créer le dossier de sauvegarde s'il n'existe pas
            if (!File::exists(storage_path('backups'))) {
                File::makeDirectory(storage_path('backups'), 0755, true);
            }
            
            // Copier la base de données
            if (File::exists($databasePath)) {
                File::copy($databasePath, $backupPath);
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Backup created successfully',
                    'backup_path' => $backupPath,
                    'size' => File::size($backupPath)
                ]);
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Database file not found'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Restaurer une sauvegarde
     */
    public function restoreBackup(Request $request)
    {
        return response()->json([
            'status' => 'development',
            'message' => 'Restore endpoint not implemented yet'
        ]);
    }
    
    /**
     * Optimiser la base de données
     */
    public function optimizeDatabase()
    {
        return response()->json([
            'status' => 'development',
            'message' => 'Optimize endpoint not implemented yet'
        ]);
    }
    
    /**
     * Obtenir les statistiques de la base de données
     */
    private function getDatabaseStats()
    {
        try {
            $databasePath = database_path('database.sqlite');
            
            $stats = [
                'exists' => File::exists($databasePath),
                'size' => File::exists($databasePath) ? File::size($databasePath) : 0,
                'size_human' => $this->formatBytes(File::exists($databasePath) ? File::size($databasePath) : 0),
                'tables' => []
            ];
            
            if ($stats['exists'] && env('DB_CONNECTION') === 'sqlite') {
                // Compter les enregistrements par table
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table'");
                
                foreach ($tables as $table) {
                    $count = DB::table($table->name)->count();
                    $stats['tables'][$table->name] = $count;
                }
                
                $stats['total_records'] = array_sum($stats['tables']);
            }
            
            return $stats;
            
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'exists' => false
            ];
        }
    }
    
    /**
     * Obtenir les informations de stockage
     */
    private function getStorageInfo()
    {
        try {
            $storagePath = storage_path();
            $backupPath = storage_path('backups');
            
            return [
                'storage_size' => $this->getDirectorySize($storagePath),
                'storage_size_human' => $this->formatBytes($this->getDirectorySize($storagePath)),
                'backups_count' => File::exists($backupPath) ? count(File::files($backupPath)) : 0,
                'backups_size' => File::exists($backupPath) ? $this->getDirectorySize($backupPath) : 0,
                'backups_size_human' => File::exists($backupPath) ? $this->formatBytes($this->getDirectorySize($backupPath)) : '0 B'
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculer la taille d'un répertoire
     */
    private function getDirectorySize($path)
    {
        if (!File::exists($path)) {
            return 0;
        }
        
        $size = 0;
        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }
        
        return $size;
    }
    
    /**
     * Formater les octets en format lisible
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}