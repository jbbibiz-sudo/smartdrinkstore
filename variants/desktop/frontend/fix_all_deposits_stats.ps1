# Fix All Deposits Stats Calls
# Ajoute try-catch dans tous les fichiers

$files = @(
    ".\src\views\Deposits.vue",
    ".\src\views\DepositsTable.vue"
)

Write-Host "CORRECTION DES APPELS STATS DEPOSITS" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

foreach ($file in $files) {
    if (-not (Test-Path $file)) {
        Write-Host "Fichier ignore: $file (non trouve)" -ForegroundColor Yellow
        continue
    }

    Write-Host "Traitement: $file" -ForegroundColor White
    
    # Sauvegarde
    Copy-Item $file "$file.backup" -Force
    
    $content = Get-Content $file -Raw -Encoding UTF8
    $modified = $false
    
    # Pattern 1: fetch direct
    if ($content -match "fetch\([^)]*deposits/stats/summary" -and 
        $content -notmatch "try\s*\{[^}]{0,200}deposits/stats/summary") {
        
        Write-Host "  - Appel fetch sans try-catch detecte" -ForegroundColor Yellow
        $modified = $true
    }
    
    # Pattern 2: await sans try-catch
    if ($content -match "await\s+[^;]*deposits/stats/summary" -and 
        $content -notmatch "try\s*\{[^}]{0,200}deposits/stats/summary") {
        
        Write-Host "  - Appel await sans try-catch detecte" -ForegroundColor Yellow
        $modified = $true
    }
    
    if ($modified) {
        Write-Host "  => Modification manuelle requise" -ForegroundColor Red
        Write-Host ""
    } else {
        Write-Host "  => Deja protege ou pas d'appel problematique" -ForegroundColor Green
        Write-Host ""
    }
}

Write-Host ""
Write-Host "ACTIONS REQUISES:" -ForegroundColor Yellow
Write-Host "=================" -ForegroundColor Yellow
Write-Host ""
Write-Host "Pour chaque fichier .vue, trouvez la ligne ~370 et ajoutez:" -ForegroundColor White
Write-Host ""
Write-Host "AVANT:" -ForegroundColor Red
Write-Host "  const response = await fetch(...'/deposits/stats/summary'...)" -ForegroundColor Gray
Write-Host "  const data = await response.json()" -ForegroundColor Gray
Write-Host ""
Write-Host "APRES:" -ForegroundColor Green
Write-Host "  try {" -ForegroundColor Cyan
Write-Host "    const response = await fetch(...'/deposits/stats/summary'...)" -ForegroundColor Cyan
Write-Host "    const data = await response.json()" -ForegroundColor Cyan
Write-Host "    // utiliser data..." -ForegroundColor Cyan
Write-Host "  } catch (error) {" -ForegroundColor Cyan
Write-Host "    console.warn('Stats indisponibles:', error.message)" -ForegroundColor Cyan
Write-Host "    // Valeurs par defaut" -ForegroundColor Cyan
Write-Host "  }" -ForegroundColor Cyan
Write-Host ""
Write-Host "Voulez-vous voir les lignes exactes ? (Y/N)" -ForegroundColor Yellow
$response = Read-Host

if ($response -eq "Y" -or $response -eq "y") {
    Write-Host ""
    Write-Host "=== Deposits.vue ligne 371 ===" -ForegroundColor Cyan
    Get-Content ".\src\views\Deposits.vue" | Select-Object -Skip 365 -First 10 | ForEach-Object { "  $_" }
    
    Write-Host ""
    Write-Host "=== DepositsTable.vue ligne 336 ===" -ForegroundColor Cyan
    Get-Content ".\src\views\DepositsTable.vue" | Select-Object -Skip 330 -First 10 | ForEach-Object { "  $_" }
}