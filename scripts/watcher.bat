@echo off
setlocal EnableExtensions EnableDelayedExpansion

:: ===== Config =====
set "TASK_NAME=Laragon Open in Sublime"
set "WATCHER_PS=C:\laragon\www\scripts\open-sublime-watcher.ps1"

:menu
cls
echo ==========================================
echo   Open in Sublime Watcher - Task Manager
echo ==========================================
echo.
echo Task name : "%TASK_NAME%"
echo Watcher   : "%WATCHER_PS%"
echo.
echo  [1] Enable (start watcher on login)
echo  [2] Disable (remove login task)
echo  [3] Status (is task installed?)
echo  [4] Run watcher now (test)
echo  [5] Exit
echo.
set /p choice=Choose an option (1-5): 

if "%choice%"=="1" goto enable
if "%choice%"=="2" goto disable
if "%choice%"=="3" goto status
if "%choice%"=="4" goto runnow
if "%choice%"=="5" goto end

echo.
echo Invalid choice. Try again.
pause
goto menu

:enable
cls
echo Enabling login task...

if not exist "%WATCHER_PS%" (
  echo.
  echo ERROR: Watcher script not found:
  echo   %WATCHER_PS%
  echo.
  echo Fix the path in this .bat file or create the watcher script.
  pause
  goto menu
)

:: Delete existing task silently (prevents "already exists" errors)
schtasks /Query /TN "%TASK_NAME%" >nul 2>&1
if %errorlevel%==0 (
  schtasks /Delete /TN "%TASK_NAME%" /F >nul 2>&1
)

:: Create task for current user at logon
:: Uses a hidden PowerShell window and bypasses execution policy for this run only
schtasks /Create ^
  /TN "%TASK_NAME%" ^
  /SC ONLOGON ^
  /RL LIMITED ^
  /TR "wscript.exe \"C:\laragon\www\scripts\open-sublime-watcher-hidden.vbs\"" ^
  >nul 2>&1

if %errorlevel%==0 (
  echo.
  echo ✅ Enabled. The watcher will start automatically when you log in.
) else (
  echo.
  echo ❌ Failed to create the task.
  echo Try running this .bat as Administrator, or check Windows Task Scheduler permissions.
)
echo.
pause
goto menu

:disable
cls
echo Disabling login task...

schtasks /Query /TN "%TASK_NAME%" >nul 2>&1
if not %errorlevel%==0 (
  echo.
  echo Task not found. Nothing to disable.
  echo.
  pause
  goto menu
)

schtasks /Delete /TN "%TASK_NAME%" /F >nul 2>&1
if %errorlevel%==0 (
  echo.
  echo ✅ Disabled. The watcher will NOT start on login.
) else (
  echo.
  echo ❌ Failed to delete the task.
  echo Try running this .bat as Administrator.
)
echo.
pause
goto menu

:status
cls
echo Checking status...
echo.

schtasks /Query /TN "%TASK_NAME%" >nul 2>&1
if %errorlevel%==0 (
  echo ✅ Task is installed/enabled: "%TASK_NAME%"
  echo.
  echo Details:
  schtasks /Query /TN "%TASK_NAME%" /V /FO LIST
) else (
  echo ❌ Task not installed: "%TASK_NAME%"
)

echo.
pause
goto menu

:runnow
cls
echo Running watcher now (foreground)...
echo Press Ctrl+C in the PowerShell window to stop it.
echo.

if not exist "%WATCHER_PS%" (
  echo ERROR: Watcher script not found:
  echo   %WATCHER_PS%
  echo.
  pause
  goto menu
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%WATCHER_PS%"
echo.
pause
goto menu

:end
endlocal
exit /b 0
