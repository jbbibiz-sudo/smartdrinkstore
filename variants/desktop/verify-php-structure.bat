@echo off
REM Chemin: Smartdrinkstore/variants/desktop/verify-php-structure.bat
REM Script de verification de la structure PHP

color 0B
echo ========================================
echo  VERIFICATION STRUCTURE PHP
echo ========================================
echo.

set PHP_PATH=electron\resources\php

echo [1/5] Verification du dossier php...
if not exist "%PHP_PATH%" (
    echo [ERREUR] Le dossier php n'existe pas !
    echo Chemin attendu: %CD%\%PHP_PATH%
    echo.
    echo Solution: Creer le dossier resources\php
    pause
    exit /b 1
)
echo [OK] Dossier php trouve

echo.
echo [2/5] Verification de php.exe...
if not exist "%PHP_PATH%\php.exe" (
    echo [ERREUR] php.exe introuvable !
    echo Chemin attendu: %CD%\%PHP_PATH%\php.exe
    echo.
    echo Solution:
    echo 1. Telecharger PHP 8.3 Non-Thread Safe x64 depuis:
    echo    https://windows.php.net/download/
    echo 2. Extraire le CONTENU du ZIP directement dans:
    echo    %CD%\%PHP_PATH%\
    echo.
    echo IMPORTANT: php.exe doit etre directement dans resources\php\
    echo PAS dans un sous-dossier !
    pause
    exit /b 1
)
echo [OK] php.exe trouve

echo.
echo [3/5] Verification du dossier ext...
if not exist "%PHP_PATH%\ext" (
    echo [AVERTISSEMENT] Dossier ext\ introuvable
    echo Les extensions PHP sont manquantes
) else (
    echo [OK] Dossier ext\ trouve
)

echo.
echo [4/5] Test de lancement de PHP...
"%PHP_PATH%\php.exe" --version >nul 2>&1
if errorlevel 1 (
    echo [ERREUR] PHP ne peut pas demarrer
    echo.
    echo Solutions possibles:
    echo 1. Installer Visual C++ Redistributable:
    echo    https://aka.ms/vs/17/release/vc_redist.x64.exe
    echo 2. Verifier que c'est bien PHP x64 (pas x86)
    echo 3. Verifier que c'est bien Non-Thread Safe
    pause
    exit /b 1
)
echo [OK] PHP demarre correctement

echo.
echo [5/5] Affichage de la version PHP...
echo ----------------------------------------
"%PHP_PATH%\php.exe" --version
echo ----------------------------------------

echo.
echo ========================================
echo  VERIFICATION TERMINEE AVEC SUCCES !
echo ========================================
echo.
echo Structure PHP correcte:
echo   %CD%\%PHP_PATH%\php.exe [OK]
echo   %CD%\%PHP_PATH%\ext\ [OK]
echo.
echo Vous pouvez maintenant lancer le build !
echo ========================================
echo.
pause
