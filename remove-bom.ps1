# Script PowerShell pour supprimer le BOM de tous les fichiers PHP
# Chemin: C:\smartdrinkstore\remove-bom.ps1

# Chemin vers votre projet Laravel
$laravelPath = "C:\smartdrinkstore\core"

Write-Host "======================================" -ForegroundColor Cyan
Write-Host "Suppression du BOM de tous les fichiers PHP" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

$filesProcessed = 0
$filesWithBOM = 0

# Fonction pour supprimer le BOM d'un fichier
function Remove-BOM {
    param (
        [string]$FilePath
    )
    
    try {
        $bytes = [System.IO.File]::ReadAllBytes($FilePath)
        
        # Verifier si le fichier commence par le BOM UTF-8 (EF BB BF)
        if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
            # Supprimer les 3 premiers octets (le BOM)
            $newBytes = $bytes[3..($bytes.Length - 1)]
            [System.IO.File]::WriteAllBytes($FilePath, $newBytes)
            
            Write-Host "  [CORRIGE] $FilePath" -ForegroundColor Green
            return $true
        }
        
        return $false
    }
    catch {
        Write-Host "  [ERREUR] $FilePath : $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Trouver et traiter tous les fichiers PHP
Write-Host "Recherche des fichiers PHP dans: $laravelPath" -ForegroundColor Yellow
Write-Host ""

Get-ChildItem -Path $laravelPath -Filter "*.php" -Recurse -File | ForEach-Object {
    $filesProcessed++
    $relativePath = $_.FullName.Replace($laravelPath, "")
    
    if (Remove-BOM -FilePath $_.FullName) {
        $filesWithBOM++
    }
}

Write-Host ""
Write-Host "======================================" -ForegroundColor Cyan
Write-Host "Terminé!" -ForegroundColor Green
Write-Host "Fichiers traités: $filesProcessed" -ForegroundColor White
Write-Host "Fichiers avec BOM corrigés: $filesWithBOM" -ForegroundColor Yellow
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Action suivante: Redémarrez votre serveur Laravel" -ForegroundColor Magenta
Write-Host "  php artisan config:clear" -ForegroundColor White
Write-Host "  php artisan cache:clear" -ForegroundColor White
Write-Host "  php artisan serve" -ForegroundColor White
