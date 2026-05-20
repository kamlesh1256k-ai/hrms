@echo off
title Miraix Activity Tracker
color 0A

cd /d "%~dp0"

set FIRST_RUN=1

:LOOP
if "%FIRST_RUN%"=="1" (
    set AT_AUTO_RESTARTED=0
    set FIRST_RUN=0
) else (
    set AT_AUTO_RESTARTED=1
    echo.
    echo [AutoRestart] Tracker stopped. Restarting in 5 seconds...
    echo [AutoRestart] Admin will be notified.
    timeout /t 5 /nobreak >nul
)

node run-tracker.js

REM If tracker exits (killed/crashed), loop restarts it
goto LOOP
