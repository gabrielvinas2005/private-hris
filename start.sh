#!/usr/bin/env bash

# ==============================================================================
# Private HRIS Platform - Unified Development Launcher
# Runs php artisan serve and npm run dev across micro-portal modules in new terminals
# and automatically opens the application in your default web browser
# ==============================================================================

# Text formatting colors
BOLD='\033[1m'
CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
RESET='\033[0m'

# Base Directory (Directory where start.sh resides)
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR" || exit 1

print_banner() {
    clear
    echo -e "${CYAN}${BOLD}"
    echo "======================================================================"
    echo "            🏢 PRIVATE HRIS PLATFORM DEVELOPMENT LAUNCHER             "
    echo "======================================================================"
    echo -e "${RESET}"
}

check_prerequisites() {
    local missing=0
    if ! command -v php &> /dev/null; then
        echo -e "${RED}[✗] PHP is not installed or not in PATH.${RESET}"
        missing=1
    fi
    if ! command -v npm &> /dev/null; then
        echo -e "${RED}[✗] Node.js / NPM is not installed or not in PATH.${RESET}"
        missing=1
    fi
    if [ $missing -eq 1 ]; then
        echo -e "${YELLOW}Please install missing prerequisites before continuing.${RESET}"
        exit 1
    fi
}

launch_in_new_terminal() {
    local title="$1"
    local rel_path="$2"
    local cmd="$3"
    local full_path="$SCRIPT_DIR/$rel_path"

    if [ ! -d "$full_path" ]; then
        echo -e "${RED}[✗] Directory $rel_path does not exist!${RESET}"
        return 1
    fi

    echo -e "${GREEN}[▶] Launching terminal for: ${BOLD}$title${RESET}"

    # Build bash execution string
    local exec_str="cd \"$full_path\" && echo -e \"\033[1;36m=== $title ===\033[0m\" && $cmd; echo; read -p 'Process finished. Press Enter to close...' "

    if command -v gnome-terminal &>/dev/null; then
        gnome-terminal --title="$title" -- bash -c "$exec_str" &
    elif command -v xfce4-terminal &>/dev/null; then
        xfce4-terminal --title="$title" -e "bash -c '$exec_str'" &
    elif command -v konsole &>/dev/null; then
        konsole --title "$title" -e bash -c "$exec_str" &
    elif command -v xterm &>/dev/null; then
        xterm -T "$title" -e bash -c "$exec_str" &
    elif command -v x-terminal-emulator &>/dev/null; then
        x-terminal-emulator -T "$title" -e bash -c "$exec_str" &
    else
        echo -e "${YELLOW}[!] No standalone GUI terminal emulator found. Running as background job...${RESET}"
        ( cd "$full_path" && eval "$cmd" ) &
    fi
}


start_backend() {
    local name="$1"
    local path="$2"
    local port="$3"
    launch_in_new_terminal "$name (Port $port)" "$path" "php artisan serve --host=0.0.0.0 --port=$port"
}

start_frontend() {
    local name="$1"
    local path="$2"
    local port="$3"
    
    if [ ! -d "$SCRIPT_DIR/$path/node_modules" ]; then
        echo -e "${YELLOW}[!] node_modules missing in $path. Installing...${RESET}"
        (cd "$SCRIPT_DIR/$path" && npm install)
    fi

    if [ -n "$port" ]; then
        launch_in_new_terminal "$name (Port $port)" "$path" "npm run dev -- --port $port --open"
    else
        launch_in_new_terminal "$name" "$path" "npm run dev -- --open"
    fi
}

run_201() {
    echo -e "${PURPLE}=== Launching 201 File Management System ===${RESET}"
    start_backend "201 Backend" "private-201/201-backend" 8082
    start_frontend "201 Frontend" "private-201/201-frontend" 5175
}

run_eportal() {
    echo -e "${PURPLE}=== Launching E-Portal (Employee Self-Service) ===${RESET}"
    start_backend "EP Backend" "private-e-portal/ep-backend" 8000
    start_frontend "EP Frontend" "private-e-portal/ep-frontend" 5171
}

run_aportal() {
    echo -e "${PURPLE}=== Launching A-Portal (Approvals & Administration) ===${RESET}"
    start_backend "AP Backend" "private-a-portal/ap-backend" 8001
    start_frontend "AP Frontend" "private-a-portal/ap-frontend" 5172
}

run_timekeeping() {
    echo -e "${PURPLE}=== Launching Timekeeping & Biometrics ===${RESET}"
    start_backend "TK Backend" "private-timekeeping/tk-backend" 8080
    start_frontend "TK Frontend" "private-timekeeping/tk-frontend" 5176
}

run_payroll() {
    echo -e "${PURPLE}=== Launching Payroll Management System ===${RESET}"
    start_backend "PR Backend" "private-payroll/pr-backend" 8003
    start_frontend "PR Frontend" "private-payroll/pr-frontend" 5174
}

run_controlpanel() {
    echo -e "${PURPLE}=== Launching System Control Panel ===${RESET}"
    start_backend "CP Backend" "private-controlpanel/cp-backend" 8002
    start_frontend "CP Frontend" "private-controlpanel/cp-frontend" 5173
}

run_all() {
    echo -e "${GREEN}${BOLD}Launching ALL 6 Micro-Portal Modules...${RESET}\n"
    run_eportal
    run_aportal
    run_timekeeping
    run_payroll
    run_201
    run_controlpanel
}

run_backends_only() {
    echo -e "${GREEN}${BOLD}Launching ALL Backend Services (PHP Artisan)...${RESET}\n"
    start_backend "EP Backend" "private-e-portal/ep-backend" 8000
    start_backend "AP Backend" "private-a-portal/ap-backend" 8001
    start_backend "TK Backend" "private-timekeeping/tk-backend" 8080
    start_backend "PR Backend" "private-payroll/pr-backend" 8003
    start_backend "201 Backend" "private-201/201-backend" 8082
    start_backend "CP Backend" "private-controlpanel/cp-backend" 8002
}

run_frontends_only() {
    echo -e "${BLUE}${BOLD}Launching ALL Frontend Portals (NPM Dev + Open Browser)...${RESET}\n"
    start_frontend "EP Frontend" "private-e-portal/ep-frontend" 5171
    start_frontend "AP Frontend" "private-a-portal/ap-frontend" 5172
    start_frontend "TK Frontend" "private-timekeeping/tk-frontend" 5176
    start_frontend "PR Frontend" "private-payroll/pr-frontend" 5174
    start_frontend "201 Frontend" "private-201/201-frontend" 5175
    start_frontend "CP Frontend" "private-controlpanel/cp-frontend" 5173
}

show_menu() {
    print_banner
    echo -e "${BOLD}Select a module to launch in new terminals (auto-opens website):${RESET}"
    echo -e "  ${CYAN}1)${RESET} All Modules (All Backends + Frontends)"
    echo -e "  ${CYAN}2)${RESET} E-Portal (Employee Self-Service -> http://localhost:5171)"
    echo -e "  ${CYAN}3)${RESET} A-Portal (Approvals & Admin -> http://localhost:5172)"
    echo -e "  ${CYAN}4)${RESET} Timekeeping & Biometrics -> http://localhost:5176"
    echo -e "  ${CYAN}5)${RESET} Payroll Management -> http://localhost:5174"
    echo -e "  ${CYAN}6)${RESET} 201 File Management -> http://localhost:5175"
    echo -e "  ${CYAN}7)${RESET} System Control Panel -> http://localhost:5173"
    echo -e "  ${CYAN}8)${RESET} Backends ONLY (All 6 PHP Artisan Servers)"
    echo -e "  ${CYAN}9)${RESET} Frontends ONLY (All 6 Vite/NPM Servers)"
    echo -e "  ${CYAN}q)${RESET} Quit / Exit"
    echo ""
    read -rp "Enter choice [1-9 or q]: " choice

    case "$choice" in
        1) run_all ;;
        2) run_eportal ;;
        3) run_aportal ;;
        4) run_timekeeping ;;
        5) run_payroll ;;
        6) run_201 ;;
        7) run_controlpanel ;;
        8) run_backends_only ;;
        9) run_frontends_only ;;
        q|Q) echo -e "${GREEN}Exiting Private HRIS Launcher.${RESET}"; exit 0 ;;
        *) echo -e "${RED}Invalid choice!${RESET}" ;;
    esac
}

# Main Execution Flow
check_prerequisites

if [ $# -eq 0 ]; then
    # Interactive Continuous Loop Mode
    while true; do
        show_menu
        echo ""
        read -rp "Press Enter to return to main menu..."
    done
else
    # CLI Argument Mode
    case "$1" in
        all|ALL) run_all ;;
        ep|e-portal|eportal) run_eportal ;;
        ap|a-portal|aportal) run_aportal ;;
        tk|timekeeping) run_timekeeping ;;
        pr|payroll) run_payroll ;;
        201|file201) run_201 ;;
        cp|controlpanel) run_controlpanel ;;
        backend|backends) run_backends_only ;;
        frontend|frontends) run_frontends_only ;;
        -h|--help)
            print_banner
            echo -e "Usage: ./start.sh [module]"
            echo -e "\nAvailable modules:"
            echo -e "  all          Run all micro-portal services"
            echo -e "  ep           E-Portal (Backend: 8000, Frontend: 5171)"
            echo -e "  ap           A-Portal (Backend: 8001, Frontend: 5172)"
            echo -e "  tk           Timekeeping (Backend: 8080, Frontend: 5176)"
            echo -e "  pr           Payroll (Backend: 8003, Frontend: 5174)"
            echo -e "  201          201 File (Backend: 8082, Frontend: 5175)"
            echo -e "  cp           Control Panel (Backend: 8002, Frontend: 5173)"
            echo -e "  backend      Run all backend services only"
            echo -e "  frontend     Run all frontend services only"
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown argument: $1${RESET}"
            echo -e "Use ${BOLD}./start.sh --help${RESET} for usage information."
            exit 1
            ;;
    esac
    echo -e "\n${GREEN}[✓] Selected service(s) launched in new terminal window(s) & opening in browser.${RESET}"
fi
