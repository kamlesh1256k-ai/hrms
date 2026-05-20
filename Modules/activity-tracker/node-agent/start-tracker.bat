@echo off
REM HRMS Activity Tracker — launcher
REM Double-click this file to start the agent.

REM Critical: unset ELECTRON_RUN_AS_NODE if it's set system-wide
set ELECTRON_RUN_AS_NODE=

REM Move to the script's own directory
cd /d "%~dp0"

REM Sanity check — Node bundled inside Electron is used; user doesn't need separate Node
if not exist "node_modules\electron\dist\electron.exe" (
    echo ERROR: Electron not installed. Please run: npm install
    pause
    exit /b 1
)

REM Launch the agent
start "" "node_modules\electron\dist\electron.exe" .
exit
