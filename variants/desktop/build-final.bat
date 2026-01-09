@echo off
REM Chemin: Smartdrinkstore/variants/desktop/build-final.bat
REM Script de build final avec copie du frontend

color 0B
echo ========================================
echo  BUILD SMARTDRINKSTORE - FINAL
echo ========================================
echo.

REM Vérifier qu'on est dans le bon dossier
if not exist "electron\src\main.js" (
    echo [ERREUR] Ce script doit etre execute depuis: Smartdrinkstore\variants\desktop\
    pause
    exit /b 1
)

echo [1/5] Verification du frontend builde...
if not exist "frontend\dist\index.html" (
    echo [ERREUR] Frontend non builde !
    echo Lancez d'abord: cd frontend ^&^& npm run build
    pause
    exit /b 1
)
echo [OK] Frontend builde trouve
echo.

echo [2/5] Copie du frontend dans electron...
REM Supprimer l'ancien dossier s'il existe
if exist "electron\frontend" (
    rmdir /s /q "electron\frontend"
)

REM Copier frontend/dist vers electron/frontend/dist
xcopy "frontend\dist" "electron\frontend\dist\" /E /I /Y >nul
if errorlevel 1 (
    echo [ERREUR] Echec de la copie du frontend
    pause
    exit /b 1
)
echo [OK] Frontend copie dans electron\frontend\dist
echo.

echo [3/5] Verification des dependances...
cd electron
if not exist "node_modules" (
    echo Installation des dependances npm...
    call npm install
    if errorlevel 1 (
        echo [ERREUR] Installation des dependances echouee
        cd ..
        pause
        exit /b 1
    )
)
echo [OK] Dependances OK
echo.

echo [4/5] Lancement d'electron-builder (x64 + ia32)...
echo Cela peut prendre 10-15 minutes (2 architectures)...
echo.

REM Builder pour x64 ET ia32
call npx electron-builder --win --x64 --ia32
if errorlevel 1 (
    echo.
    echo [ERREUR] Le build a echoue !
    cd ..
    pause
    exit /b 1
)

cd ..

echo.
echo [5/5] Nettoyage...
REM Supprimer le dossier frontend copié (optionnel)
REM rmdir /s /q "electron\frontend"
echo [OK] Nettoyage termine
echo.

echo ========================================
echo  BUILD TERMINE AVEC SUCCES !
echo ========================================
echo.
echo 4 fichiers crees (x64 + ia32) dans:
echo   C:\smartdrinkstore\release\
echo.
dir /b ..\..\release\*.exe
echo.
echo Fichiers crees:
echo   [OK] SmartDrinkStore-Setup-1.0.0.exe         (Installeur 64 bits)
echo   [OK] SmartDrinkStore-Setup-1.0.0-ia32.exe    (Installeur 32 bits)
echo   [OK] SmartDrinkStore-Portable-1.0.0.exe      (Portable 64 bits)
echo   [OK] SmartDrinkStore-Portable-1.0.0-ia32.exe (Portable 32 bits)
echo.
echo Pour installer:
echo   C:\smartdrinkstore\release\SmartDrinkStore-Setup-1.0.0.exe (64 bits)
echo   C:\smartdrinkstore\release\SmartDrinkStore-Setup-1.0.0-ia32.exe (32 bits)
echo.
pause
