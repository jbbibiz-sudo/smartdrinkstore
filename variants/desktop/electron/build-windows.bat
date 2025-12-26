@echo off
echo ================================================
echo SmartDrinkStore - Build Script
echo ================================================
echo.

REM Vérifier que nous sommes dans le bon dossier
if not exist "package.json" (
    echo ERREUR: package.json introuvable!
    echo Assurez-vous d'executer ce script depuis le dossier electron
    pause
    exit /b 1
)

echo [1/4] Nettoyage des anciens builds...
if exist "dist" rmdir /s /q dist
if exist "node_modules\.cache" rmdir /s /q node_modules\.cache

echo.
echo [2/4] Installation des dependances...
call npm install

echo.
echo [3/4] Build de l'application...
echo Quelle version voulez-vous build?
echo.
echo 1. EXE (Installateur NSIS)
echo 2. MSI (Installateur Windows)
echo 3. Portable (Sans installation)
echo 4. Tout (EXE + Portable)
echo.
set /p choice="Votre choix (1-4): "

if "%choice%"=="1" (
    echo Building EXE installer...
    call npm run build:exe
) else if "%choice%"=="2" (
    echo Building MSI installer...
    call npm run build:msi
) else if "%choice%"=="3" (
    echo Building Portable version...
    call npm run build:portable
) else if "%choice%"=="4" (
    echo Building all versions...
    call npm run build
) else (
    echo Choix invalide!
    pause
    exit /b 1
)

echo.
echo [4/4] Build termine!
echo.
echo Les fichiers sont disponibles dans le dossier: dist\
echo.

REM Ouvrir le dossier dist
if exist "dist" (
    start "" "dist"
)

echo.
echo ================================================
echo Build complete!
echo ================================================
pause
