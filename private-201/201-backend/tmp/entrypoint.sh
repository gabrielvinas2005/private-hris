#!/bin/bash

# Wait for SQL Server to be ready
echo "Waiting for SQL Server to start..."
while ! /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "$SA_PASSWORD" -C -Q "SELECT 1" > /dev/null 2>&1; do
    echo "SQL Server is not ready yet. Waiting..."
    sleep 2
done

echo "SQL Server is ready!"

# Create database if it doesn't exist
echo "Creating database wti_cp_db if it doesn't exist..."
/opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "$SA_PASSWORD" -C -Q "IF DB_ID('wti_cp_db') IS NULL CREATE DATABASE wti_cp_db;" || {
    echo "Failed to create database"
    exit 1
}

# Import data if backup file exists
if [ -f "/tmp/PTTC718.sql" ]; then
    echo "Importing data from PTTC718.sql..."
    /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "$SA_PASSWORD" -C -d wti_cp_db -i /tmp/PTTC718.sql -o /tmp/import.log || {
        echo "Data import completed with warnings (check import.log for details)"
    }
    echo "Data import completed. Check /tmp/import.log for details."
else
    echo "No backup file found at /tmp/PTTC718.sql"
fi

# Keep container running
echo "Database setup complete. SQL Server is ready for connections."
exec /opt/mssql/bin/sqlservr
