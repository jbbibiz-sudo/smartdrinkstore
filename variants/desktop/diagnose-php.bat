@echo off
REM Chemin: Smartdrinkstore/variants/desktop/diagnose-php.bat
REM Script de diagnostic PHP détaillé

color 0E
echo ========================================
echo  DIAGNOSTIC PHP DETAILLE
echo ========================================
echo.

set PHP_PATH=electron\resources\php

echo [ETAPE 1] Verification du chemin...
echo Dossier de travail: %CD%
echo Chemin PHP: %CD%\%PHP_PATH%
echo.

if not exist "%PHP_PATH%\php.exe" (
    echo [ERREUR] php.exe introuvable !
    pause
    exit /b 1
)

echo [ETAPE 2] Informations sur php.exe...
echo.
dir "%PHP_PATH%\php.exe"
echo.

echo [ETAPE 3] Test de lancement avec sortie d'erreur...
echo.
echo Commande executee: "%PHP_PATH%\php.exe" --version
echo ----------------------------------------

REM Essayer de lancer PHP et capturer l'erreur
"%PHP_PATH%\php.exe" --version 2>&1

if errorlevel 1 (
    echo ----------------------------------------
    echo.
    echo [ERREUR] PHP ne peut pas demarrer !
    echo.
    echo CODE D'ERREUR: %errorlevel%
    echo.
    echo CAUSES POSSIBLES:
    echo.
    echo 1. VCRUNTIME140.dll manquante
    echo    Solution: Installer Visual C++ Redistributable 2015-2022 x64
    echo    Lien: https://aka.ms/vs/17/release/vc_redist.x64.exe
    echo.
    echo 2. Mauvaise version de PHP
    echo    - Verifier que c'est bien x64 (pas x86)
    echo    - Verifier que c'est Non-Thread Safe (pas Thread Safe)
    echo.
    echo 3. DLL systeme manquante
    echo    - Verifier que Windows est a jour
    echo.
    echo [ETAPE 4] Verification des DLL requises...
    echo.
    
    REM Vérifier si dumpbin est disponible (Visual Studio)
    where dumpbin >nul 2>&1
    if not errorlevel 1 (
        echo Liste des DLL requises par php.exe:
        dumpbin /dependents "%PHP_PATH%\php.exe" | findstr /i ".dll"
    ) else (
        echo Note: dumpbin non disponible (installez Visual Studio pour plus de details)
    )
    
    echo.
    echo [ETAPE 5] Verification des extensions PHP...
    dir "%PHP_PATH%\ext\*.dll" | find /c ".dll"
    echo extensions trouvees dans ext\
    echo.
    
    echo ========================================
    echo ACTIONS RECOMMANDEES:
    echo ========================================
    echo.
    echo 1. Telecharger et installer:
    echo    https://aka.ms/vs/17/release/vc_redist.x64.exe
    echo.
    echo 2. Redemarrer l'ordinateur
    echo.
    echo 3. Relancer ce script
    echo.
    echo 4. Si le probleme persiste, telecharger a nouveau PHP:
    echo    https://windows.php.net/download/
    echo    Choisir: PHP 8.3.x Non-Thread Safe x64
    echo.
    pause
    exit /b 1
)

echo ----------------------------------------
echo.
echo [OK] PHP fonctionne correctement !
echo.

echo [ETAPE 4] Verification des extensions...
echo.
"%PHP_PATH%\php.exe" -m
echo.

echo [ETAPE 5] Configuration PHP...
echo.
if exist "%PHP_PATH%\php.ini" (
    echo php.ini trouve: %PHP_PATH%\php.ini
    echo.
    echo Extensions activees:
    findstr /i "^extension=" "%PHP_PATH%\php.ini"
) else (
    echo [AVERTISSEMENT] php.ini non trouve
    echo.
    if exist "%PHP_PATH%\php.ini-production" (
        echo Suggestion: Copier php.ini-production vers php.ini
        echo Commande: copy "%PHP_PATH%\php.ini-production" "%PHP_PATH%\php.ini"
    )
)

echo.
echo ========================================
echo  DIAGNOSTIC TERMINE !
echo ========================================
echo.
echo PHP est fonctionnel et pret pour le build.
echo.
pause
