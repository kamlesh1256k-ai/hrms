@echo off
echo ========================================
echo Building Miraix HR Activity Tracker
echo ========================================
echo.

REM Check if Node.js is installed
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Node.js is not installed or not in PATH
    echo Please install Node.js from https://nodejs.org/
    pause
    exit /b 1
)

REM Check if npm is available
npm --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: npm is not available
    echo Please check your Node.js installation
    pause
    exit /b 1
)

echo Node.js and npm are available
echo.

REM Navigate to node-agent directory
cd /d "%~dp0node-agent"
if %errorlevel% neq 0 (
    echo ERROR: Cannot navigate to node-agent directory
    pause
    exit /b 1
)

echo Current directory: %CD%
echo.

REM Clean previous build
echo Cleaning previous build...
if exist "dist-miraix-2.0.0" (
    rmdir /s /q "dist-miraix-2.0.0"
)
if exist "node_modules" (
    echo Removing existing node_modules...
    rmdir /s /q "node_modules"
)

REM Install dependencies
echo Installing dependencies...
npm install
if %errorlevel% neq 0 (
    echo ERROR: npm install failed
    pause
    exit /b 1
)

echo Dependencies installed successfully
echo.

REM Build the application
echo Building Miraix Activity Tracker...
npm run build
if %errorlevel% neq 0 (
    echo ERROR: Build failed
    pause
    exit /b 1
)

echo.
echo ========================================
echo BUILD SUCCESSFUL!
echo ========================================
echo.
echo The Miraix HR Activity Tracker has been built successfully!
echo.
echo Output directory: %CD%\dist-miraix-2.0.0
echo.
echo Look for: Miraix Activity Tracker Setup 2.0.0.exe
echo.
echo To install:
echo 1. Navigate to the dist-miraix-2.0.0 folder
echo 2. Run "Miraix Activity Tracker Setup 2.0.0.exe"
echo 3. Follow the installation wizard
echo.
echo After installation:
echo 1. Launch Miraix Activity Tracker
echo 2. Configure with your Miraix HR credentials
echo 3. Start tracking employee activity
echo.
pause
