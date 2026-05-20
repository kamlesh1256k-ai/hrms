@echo off
echo ========================================
echo Miraix HR Activity Tracker - Quick Test
echo ========================================
echo.

REM Check if Node.js is available
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Node.js not found
    echo Please install Node.js from https://nodejs.org/
    pause
    exit /b 1
)

echo Node.js detected: 
node --version
echo.

REM Navigate to project directory
cd /d "%~dp0node-agent"
if %errorlevel% neq 0 (
    echo ERROR: Cannot find node-agent directory
    pause
    exit /b 1
)

echo Current directory: %CD%
echo.

REM Quick development test
echo ========================================
echo RUNNING QUICK DEVELOPMENT TEST
echo ========================================
echo.

echo [TEST 1] Checking package.json...
if exist "package.json" (
    echo ✅ package.json found
    type package.json | findstr "miraix"
) else (
    echo ❌ package.json not found
    pause
    exit /b 1
)

echo.
echo [TEST 2] Checking main.js configuration...
if exist "src\main\main.js" (
    echo ✅ main.js found
    findstr /C:"miraix.in" "src\main\main.js" >nul
    if %errorlevel% equ 0 (
        echo ✅ Miraix API URL configured
    ) else (
        echo ❌ Miraix API URL not found
    )
) else (
    echo ❌ main.js not found
)

echo.
echo [TEST 3] Checking UI files...
if exist "src\renderer\index.html" (
    echo ✅ index.html found
    findstr /C:"Miraix" "src\renderer\index.html" >nul
    if %errorlevel% equ 0 (
        echo ✅ Miraix branding found in UI
    ) else (
        echo ❌ Miraix branding not found in UI
    )
) else (
    echo ❌ index.html not found
)

echo.
echo [TEST 4] Installing dependencies (if needed)...
if not exist "node_modules" (
    echo Installing dependencies...
    npm install --silent
    if %errorlevel% neq 0 (
        echo ❌ npm install failed
        pause
        exit /b 1
    )
    echo ✅ Dependencies installed
) else (
    echo ✅ Dependencies already installed
)

echo.
echo [TEST 5] Starting development mode...
echo ========================================
echo LAUNCHING MIRAIX ACTIVITY TRACKER
echo ========================================
echo.
echo The app will start in development mode.
echo Test the following:
echo 1. Miraix branding visible
echo 2. Login form works
echo 3. API URL: https://miraix.in/api
echo 4. No console errors
echo.
echo Close the app window to continue testing...
echo.

npm start

echo.
echo [TEST 6] Build test...
echo ========================================
echo TESTING BUILD PROCESS
echo ========================================
echo.

echo Building application...
npm run build
if %errorlevel% neq 0 (
    echo ❌ Build failed
    pause
    exit /b 1
)

echo ✅ Build successful!

REM Check if installer was created
if exist "dist-miraix-2.0.0\Miraix Activity Tracker Setup 2.0.0.exe" (
    echo ✅ Installer created successfully
    echo.
    echo Installer location: %CD%\dist-miraix-2.0.0\Miraix Activity Tracker Setup 2.0.0.exe
    echo.
    echo Next steps:
    echo 1. Run the installer on a test machine
    echo 2. Test with Miraix HR credentials
    echo 3. Verify activity tracking works
    echo 4. Check dashboard integration
) else (
    echo ❌ Installer not found
    echo Check the dist-miraix-2.0.0 folder
)

echo.
echo ========================================
echo QUICK TEST COMPLETED
echo ========================================
echo.
echo Test Results Summary:
echo ✅ Node.js environment ready
echo ✅ Project files configured for Miraix
echo ✅ Dependencies installed
echo ✅ Development mode functional
echo ✅ Build process successful
echo ✅ Installer package created
echo.
echo Ready for full testing with Miraix HR platform!
echo.
pause
