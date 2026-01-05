#!/bin/bash
# Script to run database migrations on local or production

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== Database Migration Tool ===${NC}"
echo ""

# Check if migration file was provided
if [ -z "$1" ]; then
    echo -e "${YELLOW}Available migrations:${NC}"
    ls -1 db/migrations/*.sql 2>/dev/null || echo "No migrations found"
    echo ""
    echo "Usage: ./run_migration.sh <migration_file.sql> [environment]"
    echo ""
    echo "Examples:"
    echo "  ./run_migration.sh CREATE_VENUE_REVIEWS_TABLE.sql local"
    echo "  ./run_migration.sh CREATE_VENUE_REVIEWS_TABLE.sql production"
    echo ""
    exit 1
fi

MIGRATION_FILE="$1"
ENVIRONMENT="${2:-local}"  # Default to local if not specified

# Check if file exists
if [ ! -f "db/migrations/$MIGRATION_FILE" ]; then
    echo -e "${RED}Error: Migration file not found: db/migrations/$MIGRATION_FILE${NC}"
    exit 1
fi

echo -e "${BLUE}Migration file:${NC} $MIGRATION_FILE"
echo -e "${BLUE}Environment:${NC} $ENVIRONMENT"
echo ""

if [ "$ENVIRONMENT" == "local" ]; then
    echo -e "${YELLOW}Running migration on LOCAL database...${NC}"
    echo "Database: punktionary_local"
    echo ""

    read -p "Continue? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Migration cancelled"
        exit 1
    fi

    echo ""
    mysql -u root punktionary_local < "db/migrations/$MIGRATION_FILE"

    if [ $? -eq 0 ]; then
        echo ""
        echo -e "${GREEN}✓ Migration completed successfully on LOCAL${NC}"
        echo ""
        echo "Verify with:"
        echo "  mysql -u root punktionary_local"
        echo "  SHOW TABLES;"
    else
        echo ""
        echo -e "${RED}✗ Migration failed${NC}"
        exit 1
    fi

elif [ "$ENVIRONMENT" == "production" ]; then
    echo -e "${RED}WARNING: Running migration on PRODUCTION database!${NC}"
    echo "Database: prod_punk"
    echo "Host: sql.punktionary.com"
    echo ""

    read -p "Are you sure you want to continue? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Migration cancelled"
        exit 1
    fi

    echo ""
    echo "Enter MySQL password for dayjoel@sql.punktionary.com:"
    mysql -h sql.punktionary.com -u dayjoel -p prod_punk < "db/migrations/$MIGRATION_FILE"

    if [ $? -eq 0 ]; then
        echo ""
        echo -e "${GREEN}✓ Migration completed successfully on PRODUCTION${NC}"
        echo ""
        echo "Verify with:"
        echo "  mysql -h sql.punktionary.com -u dayjoel -p prod_punk"
        echo "  SHOW TABLES;"
    else
        echo ""
        echo -e "${RED}✗ Migration failed${NC}"
        exit 1
    fi
else
    echo -e "${RED}Error: Invalid environment. Use 'local' or 'production'${NC}"
    exit 1
fi
