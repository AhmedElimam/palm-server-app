#!/bin/bash

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

command_exists() {
    command -v "$1" >/dev/null 2>&1
}

detect_os() {
    case "$(uname -s)" in
        Linux*)     echo "linux";;
        Darwin*)    echo "macos";;
        CYGWIN*|MINGW*|MSYS*) echo "windows";;
        *)          echo "unknown";;
    esac
}

check_prerequisites() {
    print_status "Checking prerequisites..."
    
    local missing_deps=()
    
    if ! command_exists node; then
        missing_deps+=("Node.js")
    fi
    
    if ! command_exists npm; then
        missing_deps+=("npm")
    fi
    
    if ! command_exists php; then
        missing_deps+=("PHP")
    fi
    
    if ! command_exists composer; then
        missing_deps+=("Composer")
    fi
    
    if ! command_exists go; then
        missing_deps+=("Go")
    fi
    
    if [ ${#missing_deps[@]} -ne 0 ]; then
        print_error "Missing dependencies: ${missing_deps[*]}"
        print_status "Please install the missing dependencies before running this script."
        exit 1
    fi
    
    print_success "All prerequisites are installed"
}

install_backend_deps() {
    print_status "Installing backend dependencies..."
    
    cd palm-scrapping-backend
    
    print_status "Installing Composer dependencies..."
    if composer install --no-interaction; then
        print_success "Composer dependencies installed successfully"
    else
        print_error "Failed to install Composer dependencies"
        exit 1
    fi
    
    print_status "Installing npm dependencies for backend..."
    if npm install; then
        print_success "Backend npm dependencies installed successfully"
    else
        print_error "Failed to install backend npm dependencies"
        exit 1
    fi
    
    cd ..
}

run_migrations() {
    print_status "Running database migrations..."
    
    cd palm-scrapping-backend
    
    if php artisan migrate --force; then
        print_success "Database migrations completed successfully"
    else
        print_error "Failed to run database migrations"
        exit 1
    fi
    
    cd ..
}

install_frontend_deps() {
    print_status "Installing frontend dependencies..."
    
    cd palm-scrapping-frontend
    
    if npm install; then
        print_success "Frontend dependencies installed successfully"
    else
        print_error "Failed to install frontend dependencies"
        exit 1
    fi
    
    cd ..
}

install_proxy_deps() {
    print_status "Installing proxy service dependencies..."
    
    cd proxy-service
    
    if go mod download; then
        print_success "Proxy service dependencies installed successfully"
    else
        print_error "Failed to install proxy service dependencies"
        exit 1
    fi
    
    cd ..
}

start_services() {
    print_status "Starting all services..."
    
    mkdir -p .temp
    
    cleanup() {
        print_status "Shutting down services..."
        if [ -f .temp/backend.pid ]; then
            kill $(cat .temp/backend.pid) 2>/dev/null || true
            rm -f .temp/backend.pid
        fi
        if [ -f .temp/frontend.pid ]; then
            kill $(cat .temp/frontend.pid) 2>/dev/null || true
            rm -f .temp/frontend.pid
        fi
        if [ -f .temp/proxy.pid ]; then
            kill $(cat .temp/proxy.pid) 2>/dev/null || true
            rm -f .temp/proxy.pid
        fi
        rm -rf .temp
        exit 0
    }
    
    trap cleanup SIGINT SIGTERM EXIT
    
    print_status "Starting Laravel backend server..."
    cd palm-scrapping-backend
    php artisan serve --host=0.0.0.0 --port=8000 > ../.temp/backend.log 2>&1 &
    echo $! > ../.temp/backend.pid
    cd ..
    print_success "Backend server started on http://localhost:8000"
    
    print_status "Starting frontend development server..."
    cd palm-scrapping-frontend
    npm run dev > ../.temp/frontend.log 2>&1 &
    echo $! > ../.temp/frontend.pid
    cd ..
    print_success "Frontend server started (check logs for URL)"
    
    print_status "Starting Go proxy service..."
    cd proxy-service
    go run main.go > ../.temp/proxy.log 2>&1 &
    echo $! > ../.temp/proxy.pid
    cd ..
    print_success "Proxy service started"
    
    print_success "All services are now running!"
    print_status "Press Ctrl+C to stop all services"
    
    wait
}

main() {
    print_status "Starting palm-scrapping project setup..."
    print_status "Detected OS: $(detect_os)"
    
    if [ ! -d "palm-scrapping-backend" ] || [ ! -d "palm-scrapping-frontend" ] || [ ! -d "proxy-service" ]; then
        print_error "Please run this script from the project root directory"
        print_status "Expected directories: palm-scrapping-backend, palm-scrapping-frontend, proxy-service"
        exit 1
    fi
    
    check_prerequisites
    
    install_backend_deps
    run_migrations
    install_frontend_deps
    install_proxy_deps
    
    start_services
}

main "$@" 