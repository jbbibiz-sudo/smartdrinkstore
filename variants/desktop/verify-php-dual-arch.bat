@echo off
REM Chemin: Smartdrinkstore/variants/desktop/verify-php-dual-arch.bat
REM Script de verification PHP 32 bits ET 64 bits

color 0B
echo ========================================
echo  VERIFICATION PHP DUAL ARCHITECTURE
echo ========================================
echo.

set PHP32_PATH=electron\resources\php32
set PHP64_PATH=electron\resources\php64
set ERRORS=0

REM ============================================
echo [1/6] Verification dossier PHP 32 bits...
REM ============================================
if not exist "%PHP32_PATH%" (
    echo [ERREUR] Dossier php32 introuvable !
    echo Chemin attendu: %CD%\%PHP32_PATH%
    set /a ERRORS+=1
) else (
    echo [OK] Dossier php32 trouve
)

echo.
REM ============================================
echo [2/6] Verification dossier PHP 64 bits...
REM ============================================
if not exist "%PHP64_PATH%" (
    echo [ERREUR] Dossier php64 introuvable !
    echo Chemin attendu: %CD%\%PHP64_PATH%
    set /a ERRORS+=1
) else (
    echo [OK] Dossier php64 trouve
)

echo.
REM ============================================
echo [3/6] Verification php.exe 32 bits...
REM ============================================
if not exist "%PHP32_PATH%\php.exe" (
    echo [ERREUR] php.exe 32 bits introuvable !
    echo Chemin attendu: %CD%\%PHP32_PATH%\php.exe
    echo.
    echo Solution:
    echo 1. Telecharger PHP 8.3 NTS x86 depuis:
    echo    https://windows.php.net/download/
    echo 2. Extraire dans: %CD%\%PHP32_PATH%\
    set /a ERRORS+=1
) else (
    echo [OK] php.exe 32 bits trouve
    dir "%PHP32_PATH%\php.exe" | find "php.exe"
)

echo.
REM ============================================
echo [4/6] Verification php.exe 64 bits...
REM ============================================
if not exist "%PHP64_PATH%\php.exe" (
    echo [ERREUR] php.exe 64 bits introuvable !
    echo Chemin attendu: %CD%\%PHP64_PATH%\php.exe
    echo.
    echo Solution:
    echo 1. Telecharger PHP 8.3 NTS x64 depuis:
    echo    https://windows.php.net/download/
    echo 2. Extraire dans: %CD%\%PHP64_PATH%\
    set /a ERRORS+=1
) else (
    echo [OK] php.exe 64 bits trouve
    dir "%PHP64_PATH%\php.exe" | find "php.exe"
)

echo.
REM ============================================
echo [5/6] Test de lancement PHP 32 bits...
REM ============================================
if exist "%PHP32_PATH%\php.exe" (
    echo Commande: "%PHP32_PATH%\php.exe" --version
    echo ----------------------------------------
    "%PHP32_PATH%\php.exe" --version 2>&1
    if errorlevel 1 (
        echo ----------------------------------------
        echo [ERREUR] PHP 32 bits ne peut pas demarrer !
        echo.
        echo Solution:
        echo   Installer Visual C++ Redistributable x86:
        echo   https://aka.ms/vs/17/release/vc_redist.x86.exe
        set /a ERRORS+=1
    ) else (
        echo ----------------------------------------
        echo [OK] PHP 32 bits fonctionne !
    )
) else (
    echo [SKIP] php.exe 32 bits absent, test ignore
)

echo.
REM ============================================
echo [6/6] Test de lancement PHP 64 bits...
REM ============================================
if exist "%PHP64_PATH%\php.exe" (
    echo Commande: "%PHP64_PATH%\php.exe" --version
    echo ----------------------------------------
    "%PHP64_PATH%\php.exe" --version 2>&1
    if errorlevel 1 (
        echo ----------------------------------------
        echo [ERREUR] PHP 64 bits ne peut pas demarrer !
        echo.
        echo Solution:
        echo   Installer Visual C++ Redistributable x64:
        echo   https://aka.ms/vs/17/release/vc_redist.x64.exe
        set /a ERRORS+=1
    ) else (
        echo ----------------------------------------
        echo [OK] PHP 64 bits fonctionne !
    )
) else (
    echo [SKIP] php.exe 64 bits absent, test ignore
)

echo.
REM ============================================
echo RESUME
REM ============================================
if %ERRORS% GTR 0 (
    color 0C
    echo ========================================
    echo  %ERRORS% ERREUR^(S^) DETECTEE^(S^) !
    echo ========================================
    echo.
    echo Consultez SETUP_PHP_DUAL_ARCH.md pour les solutions.
    echo.
    pause
    exit /b 1
) else (
    color 0A
    echo ========================================
    echo  VERIFICATION REUSSIE !
    echo ========================================
    echo.
    echo PHP 32 bits: [OK]
    echo PHP 64 bits: [OK]
    echo.
    echo Structure correcte:
    echo   %CD%\%PHP32_PATH%\php.exe [OK]
    echo   %CD%\%PHP64_PATH%\php.exe [OK]
    echo.
    echo Vous pouvez maintenant builder l'application:
    echo   npx electron-builder --win
    echo.
    echo Cela creera 4 fichiers:
    echo   - Setup x64
    echo   - Setup x86 (ia32)
    echo   - Portable x64
    echo   - Portable x86 (ia32)
    echo.
    pause
)
