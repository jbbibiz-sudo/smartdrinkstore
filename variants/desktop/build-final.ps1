# Chemin: Smartdrinkstore/variants/desktop/build-final.ps1
# Script de build final avec copie du frontend

Write-Host "========================================" -ForegroundColor Cyan
Write-Host " BUILD SMARTDRINKSTORE - FINAL" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$ErrorActionPreference = "Stop"
$ProgressPreference = 'SilentlyContinue'

# Vérifier qu'on est dans le bon dossier
if (-not (Test-Path "electron\src\main.js")) {
    Write-Host "[ERREUR] Ce script doit être exécuté depuis: Smartdrinkstore\variants\desktop\" -ForegroundColor Red
    Read-Host "Appuyez sur Entrée pour quitter"
    exit 1
}

Write-Host "[1/5] Vérification du frontend buildé..." -ForegroundColor Yellow
if (-not (Test-Path "frontend\dist\index.html")) {
    Write-Host "[ERREUR] Frontend non buildé !" -ForegroundColor Red
    Write-Host "Lancez d'abord: cd frontend && npm run build" -ForegroundColor Yellow
    Read-Host "Appuyez sur Entrée pour quitter"
    exit 1
}
Write-Host "[OK] Frontend buildé trouvé" -ForegroundColor Green
Write-Host ""

Write-Host "[2/5] Copie du frontend dans electron..." -ForegroundColor Yellow
# Supprimer l'ancien dossier s'il existe
if (Test-Path "electron\frontend") {
    Remove-Item "electron\frontend" -Recurse -Force
}

# Copier frontend/dist vers electron/frontend/dist
Copy-Item "frontend\dist" "electron\frontend\dist" -Recurse -Force
Write-Host "[OK] Frontend copié dans electron\frontend\dist" -ForegroundColor Green
Write-Host ""

Write-Host "[3/5] Vérification des dépendances..." -ForegroundColor Yellow
Set-Location electron
if (-not (Test-Path "node_modules")) {
    Write-Host "Installation des dépendances npm..." -ForegroundColor Yellow
    npm install
}
Write-Host "[OK] Dépendances OK" -ForegroundColor Green
Write-Host ""

Write-Host "[4/5] Lancement d'electron-builder (x64 + ia32)..." -ForegroundColor Yellow
Write-Host "Cela peut prendre 10-15 minutes (2 architectures)..." -ForegroundColor Cyan
Write-Host ""

# Builder pour x64 ET ia32
npx electron-builder --win --x64 --ia32

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "[ERREUR] Le build a échoué !" -ForegroundColor Red
    Set-Location ..
    Read-Host "Appuyez sur Entrée pour quitter"
    exit 1
}

Set-Location ..

Write-Host ""
Write-Host "[5/5] Nettoyage..." -ForegroundColor Yellow
# Supprimer le dossier frontend copié (optionnel)
# Remove-Item "electron\frontend" -Recurse -Force
Write-Host "[OK] Nettoyage terminé" -ForegroundColor Green

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host " BUILD TERMINÉ AVEC SUCCÈS !" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "4 fichiers créés (x64 + ia32) dans:" -ForegroundColor Cyan
Write-Host "  C:\smartdrinkstore\release\" -ForegroundColor White
Write-Host ""

Get-ChildItem "..\..\release\*.exe" | Format-Table Name, @{Name="Taille (MB)";Expression={[math]::Round($_.Length/1MB, 2)}} -AutoSize

Write-Host ""
Write-Host "Fichiers créés:" -ForegroundColor Cyan
Write-Host "  ✅ SmartDrinkStore-Setup-1.0.0.exe         (Installeur 64 bits)" -ForegroundColor White
Write-Host "  ✅ SmartDrinkStore-Setup-1.0.0-ia32.exe    (Installeur 32 bits)" -ForegroundColor White
Write-Host "  ✅ SmartDrinkStore-Portable-1.0.0.exe      (Portable 64 bits)" -ForegroundColor White
Write-Host "  ✅ SmartDrinkStore-Portable-1.0.0-ia32.exe (Portable 32 bits)" -ForegroundColor White
Write-Host ""
Write-Host "Pour installer:" -ForegroundColor Cyan
Write-Host "  C:\smartdrinkstore\release\SmartDrinkStore-Setup-1.0.0.exe" -ForegroundColor White
Write-Host ""

Read-Host "Appuyez sur Entrée pour quitter"
