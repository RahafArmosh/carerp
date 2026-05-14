# Warehouse Stock Count Import - Server Fix Guide

## Changes Made

### 1. **Controller Improvements** (`WarehouseController.php`)

- **Enhanced AJAX Detection**: Now checks multiple ways to detect AJAX requests:
  - `$request->ajax()`
  - `$request->wantsJson()`
  - `$request->expectsJson()`
  - `$request->header('X-Requested-With') === 'XMLHttpRequest'`

- **Better File Handling**:
  - Validates file upload before processing
  - Handles file path issues (fallback to temporary storage)
  - Increased memory limit to 512M
  - Increased execution time to 5 minutes

- **Comprehensive Error Handling**:
  - All error responses now return JSON for AJAX requests
  - Detailed logging for debugging
  - Better error messages

### 2. **JavaScript Improvements** (`import_stock_count_single.blade.php`)

- **Event Delegation**: Handles dynamically loaded content
- **CSRF Token Handling**: Properly reads and sends CSRF token
- **Better Error Detection**: Handles network, validation, and server errors
- **Console Logging**: Added detailed logging for debugging

## Server-Specific Issues to Check

### 1. **PHP Configuration**

Check your server's PHP configuration:

```bash
# Check upload limits
php -i | grep upload_max_filesize
php -i | grep post_max_size
php -i | grep max_execution_time
php -i | grep memory_limit
```

**Recommended Settings:**
- `upload_max_filesize = 20M` (or higher)
- `post_max_size = 25M` (should be larger than upload_max_filesize)
- `max_execution_time = 300` (5 minutes)
- `memory_limit = 512M` (or higher)

### 2. **File Permissions**

Ensure Laravel can write to storage:

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### 3. **Laravel Excel Package**

Verify the package is installed on server:

```bash
composer show maatwebsite/excel
```

If not installed:
```bash
composer require maatwebsite/excel
```

### 4. **Check Server Logs**

Check Laravel logs for errors:
```bash
tail -f storage/logs/laravel.log
```

### 5. **Check Browser Console**

Open browser developer tools (F12) and check:
- **Console tab**: For JavaScript errors
- **Network tab**: For AJAX request details
  - Check if request is being sent
  - Check response status code
  - Check response body

### 6. **Common Server Issues**

1. **CSRF Token Mismatch (419 Error)**
   - Check session configuration
   - Ensure cookies are enabled
   - Check `APP_URL` in `.env` matches server URL

2. **File Upload Size Limit**
   - Check PHP `upload_max_filesize`
   - Check PHP `post_max_size`
   - Check web server limits (nginx/apache)

3. **Memory/Time Limits**
   - Check PHP `memory_limit`
   - Check PHP `max_execution_time`
   - Check web server timeouts

4. **Path Issues**
   - Ensure `storage/app/temp` directory exists
   - Check file permissions on storage directory

## Testing Steps

1. **Open Browser Console** (F12)
2. **Click Import Button**
3. **Select File and Submit**
4. **Check Console** for any errors
5. **Check Network Tab** for the AJAX request:
   - Status code should be 200 (success) or 422/500 (error)
   - Response should be JSON
6. **Check Server Logs** (`storage/logs/laravel.log`)

## Debugging

If import still fails, check:

1. **Server Logs**: `storage/logs/laravel.log`
2. **Browser Console**: JavaScript errors
3. **Network Tab**: AJAX request/response
4. **PHP Error Log**: Server PHP error log
5. **Web Server Logs**: Nginx/Apache error logs

## Quick Fix Commands

```bash
# Clear Laravel cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Fix permissions
chmod -R 775 storage bootstrap/cache

# Check PHP configuration
php -i | grep -E "upload_max_filesize|post_max_size|memory_limit|max_execution_time"
```

