# Fix Deposits Stats - Dashboard
# Usage: .\fix_deposits_stats.ps1

Write-Host "CORRECTION DES STATISTIQUES CONSIGNES" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

# Chercher le fichier du dashboard qui charge les stats
$possibleFiles = @(
    ".\src\modules\module-5-data-loaders.js",
    ".\src\App.vue",
    ".\src\views\Dashboard.vue",
    ".\src\components\Dashboard.vue"
)

$targetFile = $null

foreach ($file in $possibleFiles) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw -Encoding UTF8
        if ($content -match "deposits/stats/summary" -or $content -match "loadDeposits" -or $content -match "getStatistics") {
            $targetFile = $file
            Write-Host "Fichier trouve: $file" -ForegroundColor Green
            break
        }
    }
}

if (-not $targetFile) {
    Write-Host "Recherche dans tous les fichiers JS..." -ForegroundColor Yellow
    $found = Get-ChildItem -Path .\src -Recurse -Filter "*.js" | Select-String "deposits/stats/summary" | Select-Object -First 1
    
    if ($found) {
        $targetFile = $found.Path
        Write-Host "Fichier trouve: $targetFile" -ForegroundColor Green
    } else {
        Write-Host "Aucun fichier trouvé contenant l'appel API" -ForegroundColor Red
        Write-Host ""
        Write-Host "CORRECTION MANUELLE REQUISE:" -ForegroundColor Yellow
        Write-Host "Cherchez dans votre code ou il y a:" -ForegroundColor White
        Write-Host "  - fetch(...'/deposits/stats/summary')" -ForegroundColor Cyan
        Write-Host "  - ou une fonction loadDeposits()" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "Ajoutez un try-catch autour de l'appel API" -ForegroundColor White
        exit 1
    }
}

Write-Host ""
Write-Host "Sauvegarde..." -ForegroundColor Yellow
Copy-Item $targetFile "$targetFile.backup" -Force

$content = Get-Content $targetFile -Raw -Encoding UTF8

# Pattern pour trouver l'appel API sans gestion d'erreur
$patterns = @(
    '(?s)(const response = await fetch\([^)]*deposits/stats/summary[^;]+;)',
    '(?s)(fetch\([^)]*deposits/stats/summary[^}]+\})',
    '(?s)(await\s+\w+\.get\([''"].*deposits/stats/summary[''"]\)[^;]*;)'
)

$modified = $false

foreach ($pattern in $patterns) {
    if ($content -match $pattern) {
        Write-Host "Pattern trouve, ajout du try-catch..." -ForegroundColor Yellow
        
        # Ajouter try-catch si absent
        if ($content -notmatch 'try\s*\{[^}]*deposits/stats/summary') {
            # Simplification: afficher un message plutot que de modifier
            Write-Host "Modification automatique complexe detectee" -ForegroundColor Yellow
            $modified = $true
            break
        }
    }
}

if ($modified -or ($content -match 'deposits/stats/summary' -and $content -notmatch 'try.*deposits/stats/summary')) {
    Write-Host ""
    Write-Host "MODIFICATION MANUELLE REQUISE" -ForegroundColor Yellow
    Write-Host "================================" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Fichier: $targetFile" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Cherchez ce code:" -ForegroundColor White
    Write-Host "  const response = await fetch(...'/deposits/stats/summary'...)" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Et entourez-le d'un try-catch:" -ForegroundColor White
    Write-Host ""
    Write-Host "  try {" -ForegroundColor Green
    Write-Host "    const response = await fetch(...);" -ForegroundColor Green
    Write-Host "    // ... votre code ..." -ForegroundColor Green
    Write-Host "  } catch (error) {" -ForegroundColor Green
    Write-Host "    console.warn('Stats indisponibles:', error);" -ForegroundColor Green
    Write-Host "    // Utiliser valeurs par defaut" -ForegroundColor Green
    Write-Host "  }" -ForegroundColor Green
} else {
    Write-Host "Aucune modification necessaire ou deja corrige" -ForegroundColor Green
}

Write-Host ""
Write-Host "PROCHAINE ETAPE: Backend" -ForegroundColor Yellow
Write-Host "=========================" -ForegroundColor Yellow
Write-Host "1. Voir les artifacts pour le code PHP" -ForegroundColor White
Write-Host "2. Modifier DepositController.php" -ForegroundColor White
Write-Host "3. Verifier routes/api.php" -ForegroundColor White