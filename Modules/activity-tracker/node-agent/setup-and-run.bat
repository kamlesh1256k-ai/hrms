@echo off
title Miraix Activity Tracker Setup
color 0A

echo =========================================
echo    MIRAIX HRMS - Activity Tracker Setup
echo =========================================
echo.

REM Check if Node.js is installed
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Node.js is not installed!
    echo Please install Node.js from https://nodejs.org
    echo.
    pause
    exit /b 1
)

REM Create config directory
if not exist "%APPDATA%\hrms-desktop" mkdir "%APPDATA%\hrms-desktop"

REM Check if already configured
if exist "%APPDATA%\hrms-desktop\hrms-tracker-config.json" (
    echo Config found. Starting tracker...
    goto START_TRACKER
)

REM First time setup
echo Please enter your login details:
echo.
set /p EMAIL=Enter your Email:
set /p PASSWORD=Enter your Password:
echo.

REM Save config
(
echo {
echo     "userEmail": "%EMAIL%",
echo     "password": "%PASSWORD%",
echo     "deviceName": "%COMPUTERNAME%",
echo     "apiUrl": "https://miraix.in/api",
echo     "consentAccepted": true,
echo     "screenshotIntervalMin": 5,
echo     "heartbeatIntervalMin": 1,
echo     "activityIntervalSec": 30,
echo     "autoTrack": true,
echo     "autoStart": true
echo }
) > "%APPDATA%\hrms-desktop\hrms-tracker-config.json"

echo Config saved!
echo.

REM Install dependencies
echo Installing dependencies...
cd /d "%~dp0"
npm install --silent
echo.

:START_TRACKER
echo Starting Miraix Activity Tracker...
echo (Do not close this window)
echo.
cd /d "%~dp0"
node run-tracker.js

pause
