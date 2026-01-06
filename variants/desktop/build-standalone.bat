@echo off
REM Chemin: Smartdrinkstore/variants/desktop/build-standalone.bat
REM Script de build pour créer l'application standalone

echo ========================================
echo  BUILD SMARTDRINKSTORE STANDALONE
echo ========================================
echo.

REM Couleurs (optionnel)
color 0A

REM Vérifier qu'on est dans le bon dossier
if not exist "electron\src\main.js" (
    echo [ERREUR] Ce script doit etre execute depuis: Smartdrinkstore\variants\desktop\
    pause
    exit /b 1
)

echo [1/6] Verification des prerequis...
echo.

REM Vérifier Node.js
node --version >nul 2>&1
if errorlevel 1 (
    echo [ERREUR] Node.js n'est pas installe ou pas dans le PATH
    echo Telechargez Node.js depuis: https://nodejs.org/
    pause
    exit /b 1
)

REM Vérifier npm
npm --version >nul 2>&1
if errorlevel 1 (
    echo [ERREUR] npm n'est pas installe
    pause
    exit /b 1
)

REM Vérifier PHP portable
if not exist "electron\resources\php\php.exe" (
    echo [ERREUR] PHP portable non trouve dans: electron\resources\php\
    echo.
    echo Veuillez telecharger PHP 8.3 Non-Thread Safe depuis:
    echo https://windows.php.net/download/
    echo.
    echo Et extraire dans: electron\resources\php\
    pause
    exit /b 1
)

echo [OK] Node.js: 
node --version
echo [OK] npm: 
npm --version
echo [OK] PHP portable trouve
echo.

REM ========================================
echo [2/6] Installation des dependances Frontend...
echo.
cd frontend
if not exist "node_modules" (
    echo Installation des packages npm...
    call npm install
    if errorlevel 1 (
        echo [ERREUR] Echec de l'installation des dependances frontend
        cd ..
        pause
        exit /b 1
    )
) else (
    echo [OK] node_modules deja present
)
cd ..

REM ========================================
echo [3/6] Installation des dependances Electron...
echo.
cd electron
if not exist "node_modules" (
    echo Installation des packages npm...
    call npm install
    if errorlevel 1 (
        echo [ERREUR] Echec de l'installation des dependances electron
        cd ..
        pause
        exit /b 1
    )
) else (
    echo [OK] node_modules deja present
)
cd ..

REM ========================================
echo [4/6] Installation des dependances Laravel...
echo.
cd ..\..\core

REM Vérifier si composer est installé
composer --version >nul 2>&1
if errorlevel 1 (
    echo [AVERTISSEMENT] Composer n'est pas installe
    echo Les dependances Laravel devront etre installees manuellement
    echo Telechargez Composer depuis: https://getcomposer.org/
) else (
    if not exist "vendor" (
        echo Installation des packages Composer...
        call composer install --no-dev --optimize-autoloader
        if errorlevel 1 (
            echo [ERREUR] Echec de l'installation des dependances Laravel
            cd ..\variants\desktop
            pause
            exit /b 1
        )
    ) else (
        echo [OK] vendor deja present
        echo Mise a jour des dependances...
        call composer install --no-dev --optimize-autoloader
    )
)

cd ..\variants\desktop

REM ========================================
echo [5/6] Build du Frontend Vue...
echo.
cd frontend
echo Compilation de l'application Vue...
call npm run build
if errorlevel 1 (
    echo [ERREUR] Echec du build frontend
    cd ..
    pause
    exit /b 1
)
echo [OK] Frontend builde avec succes
cd ..

REM ========================================
echo [6/6] Creation de l'executable avec Electron Builder...
echo.
cd electron

REM Copier le .env de production
if not exist "resources\.env.production" (
    echo [ERREUR] Fichier .env.production manquant dans resources\
    cd ..
    pause
    exit /b 1
)

echo Lancement d'Electron Builder...
call npx electron-builder --win --x64
if errorlevel 1 (
    echo [ERREUR] Echec de la creation de l'executable
    cd ..
    pause
    exit /b 1
)

cd ..

REM ========================================
echo.
echo ========================================
echo  BUILD TERMINE AVEC SUCCES!
echo ========================================
echo.
echo Les fichiers generes se trouvent dans:
echo   %cd%\..\..\release\
echo.
echo Fichiers crees:
dir /b ..\..\release\*.exe 2>nul
dir /b ..\..\release\*.msi 2>nul
echo.
echo Vous pouvez maintenant distribuer ces fichiers!
echo ========================================
echo.
pause
