; Custom NSIS script — Jemini Activity Tracker
; Locks down tracker so non-admin user can't disable it:
;   1. Boot auto-start as SYSTEM (locked, deletable only by admin)
;   2. Watchdog every 1 min as SYSTEM
;   3. HKLM\Run entry (machine-wide auto-start)
;   4. Removes scheduled task ACL for non-admin users

!macro customInstall
    StrCpy $0 "$INSTDIR\Jemini Activity Tracker.exe"

    ; --- Task 1: Boot auto-start as SYSTEM (cannot be disabled by user) ---
    nsExec::ExecToLog 'schtasks /Create /F /RL HIGHEST /RU SYSTEM /SC ONLOGON /TN "JeminiTracker" /TR "\"$0\" --auto-launched"'

    ; --- Task 2: Watchdog every 1 minute as SYSTEM ---
    nsExec::ExecToLog 'schtasks /Create /F /RL HIGHEST /RU SYSTEM /SC MINUTE /MO 1 /TN "JeminiTrackerWatchdog" /TR "cmd /c tasklist | find /i \"Jemini Activity\" || start \"\" \"$0\" --auto-launched"'

    ; --- HKLM Run entry (machine-wide, requires admin to remove) ---
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Run" "JeminiTracker" '"$0" --auto-launched'

    ; --- Lock install folder permissions: deny delete to non-admin ---
    nsExec::ExecToLog 'icacls "$INSTDIR" /deny "Users:(DE,DC)" /T /C'

    ; --- Run immediately ---
    Exec '"$0" --auto-launched'
!macroend

!macro customUnInstall
    ; Restore folder permissions
    nsExec::ExecToLog 'icacls "$INSTDIR" /reset /T /C'

    ; Remove tasks
    nsExec::ExecToLog 'schtasks /Delete /F /TN "JeminiTracker"'
    nsExec::ExecToLog 'schtasks /Delete /F /TN "JeminiTrackerWatchdog"'

    ; Remove HKLM Run entry
    DeleteRegValue HKLM "Software\Microsoft\Windows\CurrentVersion\Run" "JeminiTracker"

    ; Kill running process
    nsExec::ExecToLog 'taskkill /F /IM "Jemini Activity Tracker.exe"'
!macroend
