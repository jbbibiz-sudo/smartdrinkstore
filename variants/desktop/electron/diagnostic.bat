@echo off
echo ================================================
echo DIAGNOSTIC - SmartDrinkStore Installation
echo ================================================
echo.

echo [1] Verification de la structure des fichiers...
echo.

REM Verifier que nous sommes dans le bon dossier
if not exist "package.json" (
    echo ERREUR: package.json introuvable!
    echo Vous devez executer ce script depuis le dossier electron
    pause
    exit /b 1
)

echo Structure actuelle:
tree /F /A

echo.
echo [2] Verification des fichiers cles...
echo.

if exist "src\main.js" (
    echo [OK] src\main.js existe
) else (
    echo [ERREUR] src\main.js MANQUANT!
)

if exist "src\preload.js" (
    echo [OK] src\preload.js existe
) else (
    echo [ERREUR] src\preload.js MANQUANT!
)

if exist "public\index.html" (
    echo [OK] public\index.html existe
) else (
    echo [ERREUR] public\index.html MANQUANT!
)

if exist "build\icon.ico" (
    echo [OK] build\icon.ico existe
) else (
    echo [ATTENTION] build\icon.ico manquant (non critique)
)

echo.
echo [3] Contenu de public\:
dir /B public

echo.
echo [4] Test du build unpacked...
echo.

if exist "dist\win-unpacked" (
    echo Dossier win-unpacked trouve
    echo.
    echo Contenu:
    dir /B dist\win-unpacked
    echo.
    echo Verification des fichiers dans resources:
    if exist "dist\win-unpacked\resources" (
        dir /B /S dist\win-unpacked\resources\*.html
    )
) else (
    echo ATTENTION: Dossier win-unpacked introuvable
    echo Vous devez d'abord builder l'application
)

echo.
echo ================================================
echo SOLUTIONS SI ECRAN BLANC:
echo ================================================
echo.
echo 1. Verifiez que public\index.html existe
echo 2. Verifiez que tous les fichiers Vue.js sont dans public\
echo 3. Regardez les logs dans la console Electron
echo 4. Testez avec: npm run dev (mode developpement)
echo 5. Verifiez le chemin dans src\main.js
echo.
echo ================================================
pause
