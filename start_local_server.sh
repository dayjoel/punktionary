#!/bin/bash
# Start local development server for PUNKtionary

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== Starting PUNKtionary Local Development Server ===${NC}"
echo ""
echo -e "${GREEN}✓${NC} PHP version: $(php --version | head -n 1)"
echo -e "${GREEN}✓${NC} MySQL status: $(brew services list | grep mysql | awk '{print $2}')"
echo ""
echo -e "${GREEN}Server will start at:${NC} http://localhost:8000"
echo -e "${GREEN}Database:${NC} punktionary_local"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

# Start PHP built-in server
cd "$(dirname "$0")"
php -S localhost:8000
