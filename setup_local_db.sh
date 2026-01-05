#!/bin/bash
# Setup script for local development database

echo "=== PUNKtionary Local Database Setup ==="
echo ""

# Database credentials
LOCAL_DB="punktionary_local"
LOCAL_USER="root"
LOCAL_PASS=""

echo "Step 1: Creating local database..."
mysql -u $LOCAL_USER -e "CREATE DATABASE IF NOT EXISTS $LOCAL_DB;" 2>/dev/null

if [ $? -eq 0 ]; then
    echo "✓ Database created: $LOCAL_DB"
else
    echo "✗ Failed to create database"
    exit 1
fi

echo ""
echo "Step 2: Importing schema..."
echo "Please provide the production database dump or run migrations manually."
echo ""
echo "To export from production, run:"
echo "  mysqldump -h sql.punktionary.com -u dayjoel -p prod_punk > schema_dump.sql"
echo ""
echo "Then import locally with:"
echo "  mysql -u root punktionary_local < schema_dump.sql"
echo ""
echo "Or run migrations one by one from db/migrations/"
echo ""
echo "Setup script completed!"
