#!/bin/bash

# GitHub Smart App - One-Click Installer
# This script downloads and runs the deployment script

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Get the repository URL from the script location or default
REPO_URL="${GITHUB_REPO_URL:-https://raw.githubusercontent.com/evolvus/github-smart/main}"

print_status "GitHub Smart App Installer"
print_status "=========================="

# Download the deployment script
print_status "Downloading deployment script..."
if curl -fsSL "${REPO_URL}/deploy.sh" -o deploy.sh; then
    chmod +x deploy.sh
    print_success "Deployment script downloaded successfully"
else
    print_error "Failed to download deployment script"
    print_status "Please check your internet connection and try again"
    exit 1
fi

# Run the deployment script with all arguments passed through
print_status "Starting deployment..."
exec ./deploy.sh "$@" 