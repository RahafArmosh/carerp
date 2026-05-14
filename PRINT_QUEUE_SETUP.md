# POS Print Queue System Setup Guide

## Overview

This system solves the problem of printing POS receipts when the server and POS computer are on different networks. Instead of the server printing directly (which fails due to network isolation), print jobs are queued in the database and processed by a local print service running on the POS computer.

## How It Works

1. **User clicks "Print Direct"** → Print job is queued in the database
2. **Local print service** (running on POS computer) polls the server for pending jobs
3. **Local service** connects to printer (same network) and prints the receipt
4. **Job status** is updated in the database

## Setup Instructions

### Step 1: Run Database Migration

```bash
php artisan migrate
```

This creates the `print_jobs` table.

### Step 2: Configure Server

Add to your `.env` file:

```env
PRINT_AGENT_TOKEN=your-secure-random-token-here
```

Generate a secure token:
```bash
php artisan tinker
>>> Str::random(32);
```

### Step 3: Setup Local Print Service on POS Computer

#### Requirements:
- PHP 7.4+ installed on POS computer
- Composer installed
- POS computer must be on the same network as the printer

#### Installation:

1. **Install dependencies:**
   ```bash
   composer require mike42/escpos-php
   ```

2. **Copy `local_print_service.php` to the POS computer**

3. **Edit `local_print_service.php` and update configuration:**
   ```php
   $config = [
       'server_url' => 'https://your-server.com', // Your Laravel app URL
       'api_token' => 'your-secure-random-token-here', // Must match PRINT_AGENT_TOKEN in .env
       'poll_interval' => 3, // Check every 3 seconds
       'default_printer_ip' => '10.255.254.17', // Default printer IP
       'default_printer_port' => 9100,
   ];
   ```

4. **Test the service manually:**
   ```bash
   php local_print_service.php
   ```

#### Running as a Service:

**Windows (Task Scheduler):**

1. Open Task Scheduler
2. Create Basic Task
3. Name: "POS Print Service"
4. Trigger: "When the computer starts"
5. Action: "Start a program"
   - Program: `C:\php\php.exe` (or your PHP path)
   - Arguments: `"C:\path\to\local_print_service.php"`
   - Start in: `C:\path\to\`
6. Check "Run whether user is logged on or not"
7. Save and test

**Linux (systemd):**

Create `/etc/systemd/system/pos-print.service`:

```ini
[Unit]
Description=POS Print Service
After=network.target

[Service]
Type=simple
User=posuser
WorkingDirectory=/opt/pos-print
ExecStart=/usr/bin/php /opt/pos-print/local_print_service.php
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl enable pos-print
sudo systemctl start pos-print
sudo systemctl status pos-print
```

## API Endpoints

The system provides these endpoints for the local print service:

- `GET /api/print-jobs/pending` - Get pending print jobs
- `POST /api/print-jobs/{id}/complete` - Mark job as completed
- `POST /api/print-jobs/{id}/fail` - Mark job as failed

All endpoints require authentication via `X-Print-Service-Token` header or `token` parameter.

## Troubleshooting

### Print jobs not processing:

1. **Check if local service is running:**
   - Windows: Check Task Scheduler
   - Linux: `sudo systemctl status pos-print`

2. **Check service logs:**
   - Log file location is set in `local_print_service.php` config
   - Default: `print_service.log` in same directory

3. **Verify API token:**
   - Must match `PRINT_AGENT_TOKEN` in server `.env`
   - Must match `api_token` in `local_print_service.php`

4. **Test API connectivity:**
   ```bash
   curl -H "X-Print-Service-Token: your-token" https://your-server.com/api/print-jobs/pending
   ```

5. **Check database:**
   ```sql
   SELECT * FROM print_jobs WHERE status = 'pending' ORDER BY created_at DESC;
   ```

### Printer connection issues:

1. **Verify printer IP:**
   - Check printer network settings
   - Ping printer from POS computer: `ping 10.255.254.17`

2. **Test printer port:**
   ```bash
   telnet 10.255.254.17 9100
   ```

3. **Check firewall:**
   - Ensure port 9100 is open on printer
   - Windows Firewall may block connections

## Security Notes

- **Change the default token** - Use a strong random token
- **Use HTTPS** - The API endpoints should be accessed over HTTPS in production
- **Restrict API access** - Consider IP whitelisting for the print service endpoints
- **Monitor logs** - Regularly check for failed jobs or suspicious activity

## Monitoring

### View print job status:

```sql
-- Pending jobs
SELECT id, reference_id, printer_ip, created_at 
FROM print_jobs 
WHERE status = 'pending' 
ORDER BY created_at DESC;

-- Failed jobs
SELECT id, reference_id, error_message, created_at 
FROM print_jobs 
WHERE status = 'failed' 
ORDER BY created_at DESC 
LIMIT 10;

-- Recent completed jobs
SELECT id, reference_id, processed_at 
FROM print_jobs 
WHERE status = 'completed' 
ORDER BY processed_at DESC 
LIMIT 10;
```

## Benefits

✅ Works across different networks  
✅ No need to configure VPN or port forwarding  
✅ Automatic retry on failure  
✅ Job history and tracking  
✅ Multiple printers supported (via job configuration)  
✅ Odoo-style seamless printing experience

