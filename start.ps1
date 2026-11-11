<#
.SYNOPSIS
    Private HRIS Platform - Unified Development Launcher for Windows (PowerShell)
.DESCRIPTION
    Runs php artisan serve and npm run dev across micro-portal modules in new Windows terminal windows
    and automatically opens the application in your default web browser.
.EXAMPLE
    .\start.ps1
    .\start.ps1 all
    .\start.ps1 ep
#>

[CmdletBinding()]
param (
    [Parameter(Position = 0, Mandatory = $false)]
    [ValidateSet("all", "ep", "ap", "tk", "pr", "201", "cp", "backend", "frontend", "help")]
    [string]$Module
)

$Host.UI.RawUI.WindowTitle = "Private HRIS Platform Development Launcher"

function Write-Banner {
    Clear-Host
    Write-Host "======================================================================" -ForegroundColor Cyan
    Write-Host "            🏢 PRIVATE HRIS PLATFORM DEVELOPMENT LAUNCHER             " -ForegroundColor Cyan
    Write-Host "======================================================================" -ForegroundColor Cyan
    Write-Host ""
}

function Test-Prerequisites {
    $missing = $false
    if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
        Write-Host "[!] 'php' was not detected in PATH. Ensure PHP is installed if running backend services." -ForegroundColor Yellow
    }
    if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
        Write-Host "[!] 'npm' was not detected in PATH. Ensure Node.js is installed if running frontend portals." -ForegroundColor Yellow
    }
}

function Launch-ServiceWindow {
    param (
        [string]$Title,
        [string]$RelativePath,
        [string]$Command
    )

    $fullPath = Join-Path $PSScriptRoot $RelativePath
    if (-not (Test-Path $fullPath)) {
        Write-Host "[X] Directory $RelativePath does not exist!" -ForegroundColor Red
        return
    }

    Write-Host "[▶] Launching: " -NoNewline -ForegroundColor Green
    Write-Host $Title -ForegroundColor White

    # Launch a separate cmd console window that stays open
    Start-Process -FilePath "cmd.exe" -ArgumentList "/k", "cd /d `"$fullPath`" && title $Title && echo === $Title === && $Command"
}

function Start-Backend {
    param([string]$Name, [string]$Path, [int]$Port)
    Launch-ServiceWindow -Title "$Name (Port $Port)" -RelativePath $Path -Command "php artisan serve --port=$Port"
}

function Start-Frontend {
    param([string]$Name, [string]$Path, [int]$Port)
    $nodeModules = Join-Path (Join-Path $PSScriptRoot $Path) "node_modules"
    if (-not (Test-Path $nodeModules)) {
        Write-Host "[!] node_modules missing in $Path. Running npm install..." -ForegroundColor Yellow
        Start-Process -FilePath "cmd.exe" -ArgumentList "/c", "cd /d `"$PSScriptRoot\$Path`" && npm install" -Wait
    }

    if ($Port) {
        Launch-ServiceWindow -Title "$Name (Port $Port)" -RelativePath $Path -Command "npm run dev -- --port $Port --open"
    } else {
        Launch-ServiceWindow -Title "$Name" -RelativePath $Path -Command "npm run dev -- --open"
    }
}

function Run-201 {
    Write-Host "=== Launching 201 File Management System ===" -ForegroundColor Magenta
    Start-Backend -Name "201 Backend" -Path "private-201\201-backend" -Port 8082
    Start-Frontend -Name "201 Frontend" -Path "private-201\201-frontend" -Port 5175
}

function Run-EPortal {
    Write-Host "=== Launching E-Portal (Employee Self-Service) ===" -ForegroundColor Magenta
    Start-Backend -Name "EP Backend" -Path "private-e-portal\ep-backend" -Port 8000
    Start-Frontend -Name "EP Frontend" -Path "private-e-portal\ep-frontend" -Port 5171
}

function Run-APortal {
    Write-Host "=== Launching A-Portal (Approvals & Administration) ===" -ForegroundColor Magenta
    Start-Backend -Name "AP Backend" -Path "private-a-portal\ap-backend" -Port 8001
    Start-Frontend -Name "AP Frontend" -Path "private-a-portal\ap-frontend" -Port 5172
}

function Run-Timekeeping {
    Write-Host "=== Launching Timekeeping & Biometrics ===" -ForegroundColor Magenta
    Start-Backend -Name "TK Backend" -Path "private-timekeeping\tk-backend" -Port 8080
    Start-Frontend -Name "TK Frontend" -Path "private-timekeeping\tk-frontend" -Port 5176
}

function Run-Payroll {
    Write-Host "=== Launching Payroll Management System ===" -ForegroundColor Magenta
    Start-Backend -Name "PR Backend" -Path "private-payroll\pr-backend" -Port 8003
    Start-Frontend -Name "PR Frontend" -Path "private-payroll\pr-frontend" -Port 5174
}

function Run-ControlPanel {
    Write-Host "=== Launching System Control Panel ===" -ForegroundColor Magenta
    Start-Backend -Name "CP Backend" -Path "private-controlpanel\cp-backend" -Port 8002
    Start-Frontend -Name "CP Frontend" -Path "private-controlpanel\cp-frontend" -Port 5173
}

function Run-All {
    Write-Host "Launching ALL 6 Micro-Portal Modules..." -ForegroundColor Green
    Run-EPortal
    Run-APortal
    Run-Timekeeping
    Run-Payroll
    Run-201
    Run-ControlPanel
}

function Run-BackendsOnly {
    Write-Host "Launching ALL Backend Services (PHP Artisan)..." -ForegroundColor Green
    Start-Backend -Name "EP Backend" -Path "private-e-portal\ep-backend" -Port 8000
    Start-Backend -Name "AP Backend" -Path "private-a-portal\ap-backend" -Port 8001
    Start-Backend -Name "TK Backend" -Path "private-timekeeping\tk-backend" -Port 8080
    Start-Backend -Name "PR Backend" -Path "private-payroll\pr-backend" -Port 8003
    Start-Backend -Name "201 Backend" -Path "private-201\201-backend" -Port 8082
    Start-Backend -Name "CP Backend" -Path "private-controlpanel\cp-backend" -Port 8002
}

function Run-FrontendsOnly {
    Write-Host "Launching ALL Frontend Portals (NPM Dev + Open Browser)..." -ForegroundColor Blue
    Start-Frontend -Name "EP Frontend" -Path "private-e-portal\ep-frontend" -Port 5171
    Start-Frontend -Name "AP Frontend" -Path "private-a-portal\ap-frontend" -Port 5172
    Start-Frontend -Name "TK Frontend" -Path "private-timekeeping\tk-frontend" -Port 5176
    Start-Frontend -Name "PR Frontend" -Path "private-payroll\pr-frontend" -Port 5174
    Start-Frontend -Name "201 Frontend" -Path "private-201\201-frontend" -Port 5175
    Start-Frontend -Name "CP Frontend" -Path "private-controlpanel\cp-frontend" -Port 5173
}

function Show-Menu {
    Write-Banner
    Write-Host "Select a module to launch (auto-opens browser):" -ForegroundColor White
    Write-Host "  1) All Modules (All Backends + Frontends)" -ForegroundColor Cyan
    Write-Host "  2) E-Portal (Employee Self-Service -> http://localhost:5171)" -ForegroundColor Cyan
    Write-Host "  3) A-Portal (Approvals & Admin -> http://localhost:5172)" -ForegroundColor Cyan
    Write-Host "  4) Timekeeping & Biometrics -> http://localhost:5176" -ForegroundColor Cyan
    Write-Host "  5) Payroll Management -> http://localhost:5174" -ForegroundColor Cyan
    Write-Host "  6) 201 File Management -> http://localhost:5175" -ForegroundColor Cyan
    Write-Host "  7) System Control Panel -> http://localhost:5173" -ForegroundColor Cyan
    Write-Host "  8) Backends ONLY (All 6 PHP Artisan Servers)" -ForegroundColor Cyan
    Write-Host "  9) Frontends ONLY (All 6 Vite/NPM Servers)" -ForegroundColor Cyan
    Write-Host "  q) Quit / Exit" -ForegroundColor Cyan
    Write-Host ""
    
    $choice = Read-Host "Enter choice [1-9 or q]"
    switch ($choice) {
        "1" { Run-All }
        "2" { Run-EPortal }
        "3" { Run-APortal }
        "4" { Run-Timekeeping }
        "5" { Run-Payroll }
        "6" { Run-201 }
        "7" { Run-ControlPanel }
        "8" { Run-BackendsOnly }
        "9" { Run-FrontendsOnly }
        "q" { exit }
        default { Write-Host "Invalid choice!" -ForegroundColor Red }
    }
}

Test-Prerequisites

if ([string]::IsNullOrWhiteSpace($Module)) {
    while ($true) {
        Show-Menu
        Write-Host ""
        Read-Host "Press Enter to return to menu..."
    }
} else {
    switch ($Module) {
        "all" { Run-All }
        "ep" { Run-EPortal }
        "ap" { Run-APortal }
        "tk" { Run-Timekeeping }
        "pr" { Run-Payroll }
        "201" { Run-201 }
        "cp" { Run-ControlPanel }
        "backend" { Run-BackendsOnly }
        "frontend" { Run-FrontendsOnly }
        "help" {
            Write-Banner
            Write-Host "Usage: .\start.ps1 [all|ep|ap|tk|pr|201|cp|backend|frontend]"
            exit
        }
    }
}

