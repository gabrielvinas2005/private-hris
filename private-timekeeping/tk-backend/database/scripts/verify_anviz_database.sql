-- =============================================
-- Anviz Database Verification Script
-- Database: TEST_ANVIZ_PTTC
-- Purpose: Verify database structure and data for integration
-- =============================================

USE TEST_ANVIZ_PTTC;
GO

-- 1. Check if database exists and is accessible
PRINT '=========================================';
PRINT '1. DATABASE ACCESSIBILITY CHECK';
PRINT '=========================================';
IF DB_NAME() = 'TEST_ANVIZ_PTTC'
    PRINT '✓ Database TEST_ANVIZ_PTTC is accessible';
ELSE
    PRINT '✗ Database TEST_ANVIZ_PTTC is NOT accessible';
GO

-- 2. Check userinfo table structure
PRINT '';
PRINT '=========================================';
PRINT '2. USERINFO TABLE STRUCTURE';
PRINT '=========================================';
IF OBJECT_ID('dbo.userinfo', 'U') IS NOT NULL
BEGIN
    PRINT '✓ Table userinfo exists';
    
    -- Check for required columns
    IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.userinfo') AND name = 'userid')
        PRINT '  ✓ Column userid exists';
    ELSE
        PRINT '  ✗ Column userid is MISSING';
    
    -- Check for Anviz-specific columns
    IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.userinfo') AND name = 'UserCode')
        PRINT '  ✓ Column UserCode exists (Anviz access code)';
    ELSE
        PRINT '  ✗ Column UserCode is MISSING (required for Anviz)';
    
    IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.userinfo') AND name = 'CardNum')
        PRINT '  ✓ Column CardNum exists (alternative access code)';
    ELSE
        PRINT '  ⚠ Column CardNum not found (optional)';
    
    -- Show sample data
    PRINT '';
    PRINT 'Sample userinfo records (TOP 5):';
    SELECT TOP 5 
        userid,
        UserCode,
        ISNULL(CardNum, 'NULL') as CardNum,
        Name,
        CASE WHEN LEN(ISNULL(UserCode, '')) > 0 THEN 'Has value' ELSE 'Empty' END as usercode_status
    FROM dbo.userinfo
    ORDER BY userid;
    
    -- Count total records
    DECLARE @user_count INT;
    SELECT @user_count = COUNT(*) FROM dbo.userinfo;
    PRINT '';
    PRINT 'Total userinfo records: ' + CAST(@user_count AS VARCHAR);
END
ELSE
    PRINT '✗ Table userinfo DOES NOT EXIST';
GO

-- 3. Check checkinout table structure
PRINT '';
PRINT '=========================================';
PRINT '3. CHECKINOUT TABLE STRUCTURE';
PRINT '=========================================';
IF OBJECT_ID('dbo.checkinout', 'U') IS NOT NULL
BEGIN
    PRINT '✓ Table checkinout exists';
    
    -- Check for required columns
    IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.checkinout') AND name = 'userid')
        PRINT '  ✓ Column userid exists';
    ELSE
        PRINT '  ✗ Column userid is MISSING';
    
    IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.checkinout') AND name = 'checktime')
        PRINT '  ✓ Column checktime exists';
    ELSE
        PRINT '  ✗ Column checktime is MISSING';
    
    IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.checkinout') AND name = 'checktype')
        PRINT '  ✓ Column checktype exists';
    ELSE
        PRINT '  ✗ Column checktype is MISSING';
    
    -- Show sample data
    PRINT '';
    PRINT 'Sample checkinout records (TOP 10):';
    SELECT TOP 10 
        userid,
        checktime,
        checktype,
        CASE 
            WHEN checktype = 'I' THEN 'AM In'
            WHEN checktype = '0' THEN 'AM Out'
            WHEN checktype = '1' THEN 'PM In'
            WHEN checktype = 'O' THEN 'PM Out'
            ELSE 'Unknown: ' + checktype
        END as checktype_description
    FROM dbo.checkinout
    ORDER BY checktime DESC;
    
    -- Count total records
    DECLARE @check_count INT;
    SELECT @check_count = COUNT(*) FROM dbo.checkinout;
    PRINT '';
    PRINT 'Total checkinout records: ' + CAST(@check_count AS VARCHAR);
    
    -- Check date range
    PRINT '';
    PRINT 'Date Range:';
    SELECT 
        MIN(CONVERT(DATE, checktime)) as earliest_date,
        MAX(CONVERT(DATE, checktime)) as latest_date,
        COUNT(DISTINCT CONVERT(DATE, checktime)) as unique_days
    FROM dbo.checkinout;
    
    -- Check checktype distribution
    PRINT '';
    PRINT 'Checktype Distribution:';
    SELECT 
        checktype,
        CASE 
            WHEN checktype = 'I' THEN 'AM In'
            WHEN checktype = '0' THEN 'AM Out'
            WHEN checktype = '1' THEN 'PM In'
            WHEN checktype = 'O' THEN 'PM Out'
            ELSE 'Unknown'
        END as description,
        COUNT(*) as record_count
    FROM dbo.checkinout
    GROUP BY checktype
    ORDER BY checktype;
END
ELSE
    PRINT '✗ Table checkinout DOES NOT EXIST';
GO

-- 4. Check for orphaned records
PRINT '';
PRINT '=========================================';
PRINT '4. DATA INTEGRITY CHECK';
PRINT '=========================================';

IF OBJECT_ID('dbo.userinfo', 'U') IS NOT NULL AND OBJECT_ID('dbo.checkinout', 'U') IS NOT NULL
BEGIN
    -- Check for checkinout records without matching userinfo
    DECLARE @orphaned_count INT;
    SELECT @orphaned_count = COUNT(*)
    FROM dbo.checkinout c
    LEFT JOIN dbo.userinfo u ON c.userid = u.userid
    WHERE u.userid IS NULL;
    
    IF @orphaned_count = 0
        PRINT '✓ No orphaned checkinout records';
    ELSE
        PRINT '✗ Found ' + CAST(@orphaned_count AS VARCHAR) + ' checkinout records without matching userinfo';
    
    -- Check for users with no checkinout records
    DECLARE @no_records_count INT;
    SELECT @no_records_count = COUNT(*)
    FROM dbo.userinfo u
    LEFT JOIN dbo.checkinout c ON u.userid = c.userid
    WHERE c.userid IS NULL;
    
    IF @no_records_count = 0
        PRINT '✓ All users have checkinout records';
    ELSE
        PRINT '⚠ Found ' + CAST(@no_records_count AS VARCHAR) + ' users without checkinout records (this is OK)';
END
GO

-- 5. Check recent activity (last 7 days)
PRINT '';
PRINT '=========================================';
PRINT '5. RECENT ACTIVITY (Last 7 Days)';
PRINT '=========================================';
IF OBJECT_ID('dbo.checkinout', 'U') IS NOT NULL
BEGIN
    SELECT 
        CONVERT(DATE, checktime) as date,
        COUNT(*) as total_records,
        COUNT(DISTINCT userid) as unique_users,
        SUM(CASE WHEN checktype = 'I' THEN 1 ELSE 0 END) as am_in,
        SUM(CASE WHEN checktype = '0' THEN 1 ELSE 0 END) as am_out,
        SUM(CASE WHEN checktype = '1' THEN 1 ELSE 0 END) as pm_in,
        SUM(CASE WHEN checktype = 'O' THEN 1 ELSE 0 END) as pm_out
    FROM dbo.checkinout
    WHERE checktime >= DATEADD(DAY, -7, GETDATE())
    GROUP BY CONVERT(DATE, checktime)
    ORDER BY date DESC;
END
GO

-- 6. Sample query that matches system expectations
PRINT '';
PRINT '=========================================';
PRINT '6. SAMPLE QUERY (System Format)';
PRINT '=========================================';
PRINT 'This query shows the format expected by the system:';
GO

IF OBJECT_ID('dbo.userinfo', 'U') IS NOT NULL AND OBJECT_ID('dbo.checkinout', 'U') IS NOT NULL
BEGIN
    -- This is similar to what the system queries (using Anviz schema)
    SELECT TOP 5
        u.userid,
        u.UserCode,
        u.Name,
        CONVERT(DATE, c.checktime) as date,
        CONVERT(NVARCHAR(50), c.checktime, 101) as checktime,
        c.checktype,
        CASE 
            WHEN c.checktype = 'I' THEN 'AM In'
            WHEN c.checktype = '0' THEN 'AM Out'
            WHEN c.checktype = '1' THEN 'PM In'
            WHEN c.checktype = 'O' THEN 'PM Out'
            ELSE 'Unknown'
        END as checktype_description
    FROM dbo.userinfo u
    INNER JOIN dbo.checkinout c ON u.userid = c.userid
    ORDER BY c.checktime DESC;
END
GO

PRINT '';
PRINT '=========================================';
PRINT 'VERIFICATION COMPLETE';
PRINT '=========================================';
PRINT '';
PRINT 'If all checks pass, your database is ready for integration!';
PRINT 'Configure your .env file with:';
PRINT '  DB_DATABASE_BIO=TEST_ANVIZ_PTTC';
PRINT '';

