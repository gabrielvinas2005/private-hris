@echo off
setlocal enabledelayedexpansion

:: ==============================================================================
:: Private HRIS Platform - Windows Development Launcher
:: Runs php artisan serve and npm run dev across micro-portal modules
:: Automatically opens the website in your default browser
:: ==============================================================================

echo ======================================================================
echo            PRIVATE HRIS PLATFORM DEVELOPMENT LAUNCHER
echo ======================================================================
echo.

if "%~1"=="" goto menu
if /i "%~1"=="all" goto run_all
if /i "%~1"=="ep" goto run_ep
if /i "%~1"=="ap" goto run_ap
if /i "%~1"=="tk" goto run_tk
if /i "%~1"=="pr" goto run_pr
if /i "%~1"=="201" goto run_201
if /i "%~1"=="cp" goto run_cp
if /i "%~1"=="backend" goto run_backends
if /i "%~1"=="frontend" goto run_frontends

:menu
echo Select a module to launch:
echo   1) All Modules (All Backends + Frontends)
echo   2) E-Portal (Employee Self-Service - Port 5171)
echo   3) A-Portal (Approvals ^& Admin - Port 5172)
echo   4) Timekeeping ^& Biometrics - Port 5176
echo   5) Payroll Management - Port 5174
echo   6) 201 File Management - Port 5175
echo   7) System Control Panel - Port 5173
echo   8) Backends ONLY
echo   9) Frontends ONLY
echo   q) Quit
echo.
set /p choice="Enter choice [1-9 or q]: "

if "%choice%"=="1" goto run_all
if "%choice%"=="2" goto run_ep
if "%choice%"=="3" goto run_ap
if "%choice%"=="4" goto run_tk
if "%choice%"=="5" goto run_pr
if "%choice%"=="6" goto run_201
if "%choice%"=="7" goto run_cp
if "%choice%"=="8" goto run_backends
if "%choice%"=="9" goto run_frontends
if /i "%choice%"=="q" exit /b 0

echo Invalid choice!
exit /b 1

:run_ep
echo Starting E-Portal...
start "EP Backend" cmd /k "cd /d private-e-portal\ep-backend && php artisan serve --port=8000"
start "EP Frontend" cmd /k "cd /d private-e-portal\ep-frontend && npm run dev -- --port 5171 --open"
goto end

:run_ap
echo Starting A-Portal...
start "AP Backend" cmd /k "cd /d private-a-portal\ap-backend && php artisan serve --port=8001"
start "AP Frontend" cmd /k "cd /d private-a-portal\ap-frontend && npm run dev -- --port 5172 --open"
goto end

:run_tk
echo Starting Timekeeping...
start "TK Backend" cmd /k "cd /d private-timekeeping\tk-backend && php artisan serve --port=8080"
start "TK Frontend" cmd /k "cd /d private-timekeeping\tk-frontend && npm run dev -- --port 5176 --open"
goto end

:run_pr
echo Starting Payroll...
start "PR Backend" cmd /k "cd /d private-payroll\pr-backend && php artisan serve --port=8003"
start "PR Frontend" cmd /k "cd /d private-payroll\pr-frontend && npm run dev -- --port 5174 --open"
goto end

:run_201
echo Starting 201 File Management...
start "201 Backend" cmd /k "cd /d private-201\201-backend && php artisan serve --port=8082"
start "201 Frontend" cmd /k "cd /d private-201\201-frontend && npm run dev -- --port 5175 --open"
goto end

:run_cp
echo Starting Control Panel...
start "CP Backend" cmd /k "cd /d private-controlpanel\cp-backend && php artisan serve --port=8002"
start "CP Frontend" cmd /k "cd /d private-controlpanel\cp-frontend && npm run dev -- --port 5173 --open"
goto end

:run_all
call :run_ep
call :run_ap
call :run_tk
call :run_pr
call :run_201
call :run_cp
goto end

:run_backends
start "EP Backend" cmd /k "cd /d private-e-portal\ep-backend && php artisan serve --port=8000"
start "AP Backend" cmd /k "cd /d private-a-portal\ap-backend && php artisan serve --port=8001"
start "CP Backend" cmd /k "cd /d private-controlpanel\cp-backend && php artisan serve --port=8002"
start "PR Backend" cmd /k "cd /d private-payroll\pr-backend && php artisan serve --port=8003"
start "201 Backend" cmd /k "cd /d private-201\201-backend && php artisan serve --port=8082"
start "TK Backend" cmd /k "cd /d private-timekeeping\tk-backend && php artisan serve --port=8080"
goto end

:run_frontends
start "EP Frontend" cmd /k "cd /d private-e-portal\ep-frontend && npm run dev -- --port 5171 --open"
start "AP Frontend" cmd /k "cd /d private-a-portal\ap-frontend && npm run dev -- --port 5172 --open"
start "CP Frontend" cmd /k "cd /d private-controlpanel\cp-frontend && npm run dev -- --port 5173 --open"
start "PR Frontend" cmd /k "cd /d private-payroll\pr-frontend && npm run dev -- --port 5174 --open"
start "201 Frontend" cmd /k "cd /d private-201\201-frontend && npm run dev -- --port 5175 --open"
start "TK Frontend" cmd /k "cd /d private-timekeeping\tk-frontend && npm run dev -- --port 5176 --open"
goto end

:end
echo Services launched in separate windows and websites opened in browser.
