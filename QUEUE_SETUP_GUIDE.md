# Queue Setup Guide for Stock Import

## Overview

The Stock Import now uses Laravel queues to process imports in the background. This allows you to import large files (6000+ items) without timeouts.

## Step 1: Configure Queue Connection

### Option A: Database Queue (Recommended for Development)

1. **Add to your `.env` file:**
   ```env
   QUEUE_CONNECTION=database
   ```

2. **Create the queue tables:**
   ```bash
   php artisan queue:table
   php artisan migrate
   ```

   This creates the `jobs` and `failed_jobs` tables in your database.

### Option B: Redis Queue (Recommended for Production)

1. **Install Redis** (if not already installed)

2. **Add to your `.env` file:**
   ```env
   QUEUE_CONNECTION=redis
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

### Option C: Sync (For Testing - Runs Immediately)

If you want to test without queues, set:
```env
QUEUE_CONNECTION=sync
```

**Note:** With `sync`, imports run immediately but may timeout on large files.

## Step 2: Start the Queue Worker

### For Development (Manual)

Open a terminal/command prompt and run:

```bash
php artisan queue:work
```

**Keep this terminal open** - the queue worker needs to keep running to process jobs.

### For Production (Background Process)

#### Windows (Using Task Scheduler or Run as Service)

1. **Create a batch file** `start-queue-worker.bat`:
   ```batch
   @echo off
   cd /d D:\websites\ERP\erp_project\erp_project
   php artisan queue:work --tries=3 --timeout=3600
   ```

2. **Run it manually** or set it up as a Windows service.

#### Linux/Mac (Using Supervisor - Recommended)

1. **Install Supervisor:**
   ```bash
   sudo apt-get install supervisor  # Ubuntu/Debian
   # or
   brew install supervisor  # Mac
   ```

2. **Create config file** `/etc/supervisor/conf.d/laravel-worker.conf`:
   ```ini
   [program:laravel-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3 --timeout=3600
   autostart=true
   autorestart=true
   stopasgroup=true
   killasgroup=true
   user=www-data
   numprocs=2
   redirect_stderr=true
   stdout_logfile=/path/to/your/project/storage/logs/worker.log
   stopwaitsecs=3600
   ```

3. **Start Supervisor:**
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start laravel-worker:*
   ```

## Step 3: Monitor Queue Jobs

### Check Queue Status

```bash
# See pending jobs
php artisan queue:work --once

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Retry specific job
php artisan queue:retry {job-id}
```

### Check Logs

Monitor the Laravel log file:
```bash
tail -f storage/logs/laravel.log
```

## Step 4: Test the Import

1. **Make sure queue worker is running:**
   ```bash
   php artisan queue:work
   ```

2. **Upload your import file** through the web interface

3. **Watch the queue worker terminal** - you should see jobs being processed

4. **Check logs** for any errors:
   ```bash
   tail -f storage/logs/laravel.log
   ```

## Troubleshooting

### Import Not Processing?

1. **Check if queue worker is running:**
   ```bash
   # In another terminal, check if process is running
   ps aux | grep "queue:work"
   ```

2. **Check queue connection:**
   ```bash
   php artisan tinker
   >>> config('queue.default')
   ```
   Should return `database` or `redis`, not `sync`

3. **Check if jobs are in queue:**
   ```bash
   php artisan tinker
   >>> DB::table('jobs')->count()
   ```

4. **Check failed jobs:**
   ```bash
   php artisan queue:failed
   ```

### Jobs Stuck?

1. **Restart queue worker:**
   - Stop current worker (Ctrl+C)
   - Start again: `php artisan queue:work`

2. **Clear stuck jobs:**
   ```bash
   php artisan queue:flush
   ```

### File Not Found Error?

- Make sure the `storage/app/imports` directory exists and is writable
- Check file permissions: `chmod -R 775 storage`

## Queue Worker Options

```bash
# Basic
php artisan queue:work

# With options
php artisan queue:work \
  --queue=default \
  --tries=3 \
  --timeout=3600 \
  --sleep=3 \
  --max-jobs=1000 \
  --max-time=3600

# Process specific queue
php artisan queue:work --queue=imports

# Run once (for testing)
php artisan queue:work --once
```

## Production Recommendations

1. **Use Supervisor** or similar process manager to keep worker running
2. **Set up monitoring** to restart worker if it crashes
3. **Use Redis** for better performance with large queues
4. **Set appropriate timeouts** based on your import size
5. **Monitor failed jobs** regularly

## Quick Start Commands

```bash
# 1. Set queue connection
# Edit .env: QUEUE_CONNECTION=database

# 2. Create queue tables
php artisan queue:table
php artisan migrate

# 3. Start worker (keep terminal open)
php artisan queue:work

# 4. Upload file through web interface
# 5. Watch worker process the jobs
```

