<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS Print Service - Running</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 30px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .status.active {
            background: #10b981;
            color: white;
        }
        
        .status.inactive {
            background: #ef4444;
            color: white;
        }
        
        .status.paused {
            background: #f59e0b;
            color: white;
        }
        
        .config-section {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .config-section h2 {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5568d3;
        }
        
        .btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: #6b7280;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .jobs-section {
            margin-top: 20px;
        }
        
        .job-item {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .job-item.processing {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        
        .job-item.completed {
            border-color: #10b981;
            background: #f0fdf4;
        }
        
        .job-item.failed {
            border-color: #ef4444;
            background: #fef2f2;
        }
        
        .job-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: box-shadow 0.2s;
        }
        
        .job-item[data-job-id] {
            cursor: pointer;
        }
        
        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .job-id {
            font-weight: 600;
            color: #333;
        }
        
        .job-status {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .job-status.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .job-status.processing {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .job-status.completed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .job-status.failed {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .job-details {
            font-size: 13px;
            color: #6b7280;
        }
        
        .log-section {
            margin-top: 20px;
            max-height: 300px;
            overflow-y: auto;
            background: #1f2937;
            color: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        
        .log-entry {
            margin-bottom: 5px;
            padding: 5px 0;
            border-bottom: 1px solid #374151;
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-time {
            color: #9ca3af;
        }
        
        .log-info {
            color: #60a5fa;
        }
        
        .log-error {
            color: #f87171;
        }
        
        .log-success {
            color: #34d399;
        }
        
        .controls {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .controls .btn {
            flex: 1;
        }
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🖨️ POS Print Service</h1>
            <div id="status" class="status inactive">Stopped</div>
        </div>
        
        <div class="config-section">
            <h2>Configuration</h2>
            <div class="form-group">
                <label>Server URL</label>
                <input type="text" id="serverUrl" placeholder="https://your-server.com" value="{{ url('/') }}">
            </div>
            <div class="form-group">
                <label>API Token</label>
                <input type="password" id="apiToken" placeholder="Enter your print service token">
            </div>
            <div class="form-group">
                <label>Polling Interval (seconds)</label>
                <input type="number" id="pollInterval" value="3" min="1" max="60">
            </div>
            <div class="controls">
                <button id="startBtn" class="btn" onclick="startService()">Start Service</button>
                <button id="stopBtn" class="btn btn-secondary" onclick="stopService()" disabled>Stop Service</button>
            </div>
        </div>
        
        <div class="jobs-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0; font-size: 18px; color: #333;">Print Queue</h2>
                <div style="display: flex; gap: 10px;">
                    <button id="clearQueueBtn" class="btn btn-secondary" onclick="clearQueue()" style="padding: 8px 16px; font-size: 12px; width: auto;">
                        Clear Queue
                    </button>
                    <label style="display: flex; align-items: center; gap: 5px; font-size: 13px; cursor: pointer;">
                        <input type="checkbox" id="autoProcessToggle" onchange="toggleAutoProcess()">
                        Auto Process (Disabled - Manual Only)
                    </label>
                </div>
            </div>
            <div id="jobsList"></div>
        </div>
        
        <div class="log-section">
            <div style="margin-bottom: 10px; font-weight: 600;">Activity Log</div>
            <div id="logContainer"></div>
        </div>
    </div>

    <script>
        let pollingInterval = null;
        let isRunning = false;
        let processedJobs = new Set();
        let processingQueue = [];
        let isProcessingQueue = false;
        let autoProcessEnabled = false; // Disabled by default - manual printing only
        let allJobs = []; // Store all jobs for manual selection
        
        const serverUrlInput = document.getElementById('serverUrl');
        const apiTokenInput = document.getElementById('apiToken');
        const pollIntervalInput = document.getElementById('pollInterval');
        const statusDiv = document.getElementById('status');
        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');
        const jobsList = document.getElementById('jobsList');
        const logContainer = document.getElementById('logContainer');
        const autoProcessToggle = document.getElementById('autoProcessToggle');
        
        // Load saved configuration
        function loadConfig() {
            const saved = localStorage.getItem('printServiceConfig');
            if (saved) {
                const config = JSON.parse(saved);
                serverUrlInput.value = config.serverUrl || '';
                apiTokenInput.value = config.apiToken || '';
                pollIntervalInput.value = config.pollInterval || 3;
            }
        }
        
        // Save configuration
        function saveConfig() {
            const config = {
                serverUrl: serverUrlInput.value,
                apiToken: apiTokenInput.value,
                pollInterval: pollIntervalInput.value
            };
            localStorage.setItem('printServiceConfig', JSON.stringify(config));
        }
        
        // Log function
        function log(message, type = 'info') {
            const time = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = 'log-entry';
            
            let className = 'log-info';
            if (type === 'error') className = 'log-error';
            if (type === 'success') className = 'log-success';
            
            logEntry.innerHTML = `<span class="log-time">[${time}]</span> <span class="${className}">${message}</span>`;
            logContainer.appendChild(logEntry);
            logContainer.scrollTop = logContainer.scrollHeight;
            
            // Keep only last 50 entries
            while (logContainer.children.length > 50) {
                logContainer.removeChild(logContainer.firstChild);
            }
        }
        
        // Update status
        function updateStatus(status, text) {
            statusDiv.className = `status ${status}`;
            statusDiv.textContent = text;
        }
        
        // Get pending jobs
        async function getPendingJobs() {
            const serverUrl = serverUrlInput.value.trim();
            const token = apiTokenInput.value.trim();
            
            if (!serverUrl) {
                log('Server URL is required', 'error');
                return [];
            }
            
            // Token is optional if user is logged in (session auth will be used)
            // Token is required for local print services running outside browser
            if (!token) {
                log('Note: API Token not provided. Using session authentication if logged in.', 'info');
            }
            
            try {
                // Ensure no trailing slash in serverUrl
                const baseUrl = serverUrl.replace(/\/+$/, '');
                const url = `${baseUrl}/api/print-jobs/pending`;
                
                const response = await fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin', // Include cookies for session authentication
                    headers: {
                        'X-Print-Service-Token': token, // Token for local print services (fallback)
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    if (response.status === 401) {
                        log(`Authentication failed: ${errorData.message || 'Invalid API token'}`, 'error');
                    } else {
                        log(`HTTP Error ${response.status}: ${errorData.message || 'Unknown error'}`, 'error');
                    }
                    return [];
                }
                
                const data = await response.json();
                if (data.success) {
                    return data.jobs || [];
                } else {
                    log(`API Error: ${data.message || 'Unknown error'}`, 'error');
                    return [];
                }
            } catch (error) {
                log(`Network error: ${error.message}`, 'error');
                console.error('Get pending jobs error:', error);
                return [];
            }
        }
        
        // Mark job as complete
        async function markJobComplete(jobId) {
            const serverUrl = serverUrlInput.value.trim();
            const token = apiTokenInput.value.trim();
            
            if (!serverUrl) {
                log('Server URL is required', 'error');
                return false;
            }
            
            // Token is optional if user is logged in (session auth will be used)
            if (!token) {
                log('Note: API Token not provided. Using session authentication if logged in.', 'info');
            }
            
            try {
                // Ensure no trailing slash in serverUrl
                const baseUrl = serverUrl.replace(/\/+$/, '');
                const url = `${baseUrl}/api/print-jobs/${jobId}/complete`;
                
                const response = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin', // Include cookies for session authentication
                    headers: {
                        'X-Print-Service-Token': token, // Token for local print services (fallback)
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    let errorMsg = errorData.message || `HTTP ${response.status}`;
                    
                    // Check for CSRF token mismatch
                    if (response.status === 419 || errorMsg.includes('CSRF') || errorMsg.includes('token mismatch')) {
                        errorMsg = 'CSRF token mismatch. Please refresh the page and try again.';
                        log(`CSRF Error for job ${jobId}: ${errorMsg}`, 'error');
                        console.error('CSRF token mismatch - route may not be excluded from CSRF protection');
                    } else if (response.status === 401) {
                        log(`Authentication failed for job ${jobId}: ${errorMsg}`, 'error');
                    } else if (response.status === 404) {
                        log(`Job ${jobId} not found`, 'error');
                    } else {
                        log(`Failed to mark job ${jobId} as complete: ${errorMsg}`, 'error');
                    }
                    console.error('Mark complete error:', { jobId, status: response.status, error: errorData, url });
                    return false;
                }
                
                const data = await response.json().catch(() => ({}));
                if (data.success) {
                    return true;
                } else {
                    log(`Failed to mark job ${jobId} as complete: ${data.message || 'Unknown error'}`, 'error');
                    return false;
                }
            } catch (error) {
                log(`Failed to mark job ${jobId} as complete: ${error.message}`, 'error');
                console.error('Mark complete exception:', error);
                return false;
            }
        }
        
        // Mark job as failed
        async function markJobFailed(jobId, errorMessage) {
            const serverUrl = serverUrlInput.value.trim();
            const token = apiTokenInput.value.trim();
            
            if (!serverUrl) {
                log('Server URL is required', 'error');
                return false;
            }
            
            // Token is optional if user is logged in (session auth will be used)
            if (!token) {
                log('Note: API Token not provided. Using session authentication if logged in.', 'info');
            }
            
            try {
                // Ensure no trailing slash in serverUrl
                const baseUrl = serverUrl.replace(/\/+$/, '');
                const url = `${baseUrl}/api/print-jobs/${jobId}/fail`;
                
                // Use URLSearchParams for proper encoding
                const formData = new URLSearchParams();
                formData.append('error_message', errorMessage || 'Print failed');
                
                const response = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin', // Include cookies for session authentication
                    headers: {
                        'X-Print-Service-Token': token, // Token for local print services (fallback)
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json'
                    },
                    body: formData.toString()
                });
                
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    let errorMsg = errorData.message || `HTTP ${response.status}`;
                    
                    // Check for CSRF token mismatch
                    if (response.status === 419 || errorMsg.includes('CSRF') || errorMsg.includes('token mismatch')) {
                        errorMsg = 'CSRF token mismatch. Please refresh the page and try again.';
                        log(`CSRF Error for job ${jobId}: ${errorMsg}`, 'error');
                        console.error('CSRF token mismatch - route may not be excluded from CSRF protection');
                    } else if (response.status === 401) {
                        log(`Authentication failed for job ${jobId}: ${errorMsg}`, 'error');
                    } else if (response.status === 404) {
                        log(`Job ${jobId} not found`, 'error');
                    } else {
                        log(`Failed to mark job ${jobId} as failed: ${errorMsg}`, 'error');
                    }
                    console.error('Mark failed error:', { jobId, status: response.status, error: errorData, url });
                    return false;
                }
                
                const data = await response.json().catch(() => ({}));
                if (data.success) {
                    return true;
                } else {
                    log(`Failed to mark job ${jobId} as failed: ${data.message || 'Unknown error'}`, 'error');
                    return false;
                }
            } catch (error) {
                log(`Failed to mark job ${jobId} as failed: ${error.message}`, 'error');
                console.error('Mark failed exception:', error);
                return false;
            }
        }
        
        // Helper function to format currency
        function formatCurrency(amount) {
            if (typeof amount === 'string') {
                amount = parseFloat(amount) || 0;
            }
            return amount.toFixed(2);
        }
        
        // Print receipt using browser print - matching POS printview template
        function printReceipt(printData) {
            return new Promise((resolve, reject) => {
                try {
                    // Create a hidden iframe for printing
                    const iframe = document.createElement('iframe');
                    iframe.style.position = 'absolute';
                    iframe.style.left = '-9999px';
                    iframe.style.width = '80mm';
                    document.body.appendChild(iframe);
                    
                    const doc = iframe.contentWindow.document;
                    doc.open();
                    
                    // Build company logo URL - matching customerpayment/print.blade.php logic
                    const serverUrl = serverUrlInput.value.trim().replace(/\/+$/, '');
                    let logoUrl = '';
                    let logoFilename = '';
                    
                    // Priority: company_logo_dark > company_logo_light > company_logo > default
                    // Match customerpayment/print.blade.php: uses company_logo_dark or company_logo_light
                    if (printData.settings?.company_logo_dark) {
                        logoFilename = printData.settings.company_logo_dark;
                    } else if (printData.settings?.company_logo_light) {
                        logoFilename = printData.settings.company_logo_light;
                    } else if (printData.settings?.company_logo) {
                        logoFilename = printData.settings.company_logo;
                    } else {
                        logoFilename = 'logo-dark.png';
                    }
                    
                    // Construct logo URL - matching customerpayment/print.blade.php pattern
                    // Pattern: URL::to('/') . '/' . 'documents' . '/' . $company_logo
                    if (logoFilename) {
                        // Use /documents/ directory like customerpayment/print.blade.php
                        logoUrl = `${serverUrl}/documents/${logoFilename}`;
                    }
                    
                    // Build company logo HTML - prioritize warehouse logo
                    let logoHtml = '';
                    let warehouseLogoUrl = '';
                    if (printData.warehouse && printData.warehouse.logo) {
                        warehouseLogoUrl = `${serverUrl}/storage/uploads/warehouse_logo/${printData.warehouse.logo}`;
                        logoHtml = `<div class="company-logo"><img src="${warehouseLogoUrl}" alt="${printData.warehouse.company_name || printData.warehouse.name || 'Warehouse Logo'}" style="max-width: 200px; max-height: 100px;" onerror="console.error('Warehouse logo failed to load:', '${warehouseLogoUrl}'); this.style.display='none';" onload="console.log('Warehouse logo loaded successfully:', '${warehouseLogoUrl}');" /></div>`;
                    } else if (logoUrl && logoFilename) {
                        // Log logo URL for debugging
                        console.log('Logo URL:', logoUrl);
                        console.log('Logo filename:', logoFilename);
                        console.log('Server URL:', serverUrl);
                        
                        // Use onerror to hide image if it fails to load, but also log for debugging
                        logoHtml = `<div class="company-logo"><img src="${logoUrl}" alt="${printData.settings?.company_name || 'Company Logo'}" onerror="console.error('Logo failed to load:', '${logoUrl}'); this.style.display='none';" onload="console.log('Logo loaded successfully:', '${logoUrl}');" /></div>`;
                    } else {
                        console.warn('Logo not generated - logoUrl:', logoUrl, 'logoFilename:', logoFilename);
                    }
                    
                    // Build company info box - prioritize warehouse info
                    const companyInfo = [];
                    if (printData.warehouse && printData.warehouse.company_name) {
                        companyInfo.push(`<strong class="cmp-name">${printData.warehouse.company_name}</strong><br>`);
                    } else if (printData.settings?.company_name) {
                        companyInfo.push(`<strong class="cmp-name">${printData.settings.company_name}</strong><br>`);
                    }
                    if (printData.warehouse && printData.warehouse.address) {
                        companyInfo.push(`${printData.warehouse.address}<br>`);
                    } else {
                        if (printData.settings?.company_email) {
                            companyInfo.push(`${printData.settings.company_email}<br>`);
                        }
                        if (printData.settings?.company_address) {
                            companyInfo.push(`${printData.settings.company_address}<br>`);
                        }
                        const cityStateZip = [
                            printData.settings?.company_city || '',
                            printData.settings?.company_state || '',
                            printData.settings?.company_zipcode || ''
                        ].filter(Boolean).join(' ');
                        if (cityStateZip) {
                            companyInfo.push(`${cityStateZip}<br>`);
                        }
                        if (printData.settings?.company_country) {
                            companyInfo.push(`${printData.settings.company_country}<br>`);
                        }
                    }
                    if (printData.settings?.company_telephone) {
                        companyInfo.push(`<b>Phone:</b> ${printData.settings.company_telephone}<br>`);
                    }
                    
                    // Build customer info - matching printview.blade.php structure
                    let customerInfo = '';
                    if (printData.customer && printData.customer.name) {
                        customerInfo += `<div class="line"><b>Name:</b> ${printData.customer.name || ''}</div>`;
                        if (printData.customer.billing_address) {
                            customerInfo += `<div class="line"><b>Address:</b> ${printData.customer.billing_address || ''}</div>`;
                        }
                        if (printData.customer.email) {
                            customerInfo += `<div class="line"><b>Email:</b> ${printData.customer.email || ''}</div>`;
                        }
                        if (printData.customer.billing_phone) {
                            customerInfo += `<div class="line"><b>Phone:</b> ${printData.customer.billing_phone || ''}</div>`;
                        }
                    } else {
                        customerInfo = '<div class="line"><b>Customer:</b> Walk-in Customer</div>';
                    }
                    
                    // Build items HTML - matching printview.blade.php structure exactly
                    const itemsHtml = (printData.items || []).map(item => {
                        let itemHtml = `<div class="item">
                            <div class="item-name"><b>${item.name || ''}</b></div>`;
                        
                        // Display combo information if available
                        // Debug: log combo data to console
                        if (item.compo_id && item.compo_id != 0) {
                            console.log('Item combo data:', {
                                name: item.name,
                                compo_id: item.compo_id,
                                compo_text: item.compo_text,
                                compo_text_type: typeof item.compo_text,
                                compo_text_length: item.compo_text ? item.compo_text.length : 0
                            });
                        }
                        
                        // Check for combo information - prioritize compo_text, fallback to compo_id
                        const hasCompoText = item.compo_text && 
                                            item.compo_text !== null && 
                                            item.compo_text !== undefined && 
                                            String(item.compo_text).trim() !== '' &&
                                            String(item.compo_text).trim() !== 'null';
                        
                        const hasCompoId = item.compo_id && 
                                          item.compo_id != 0 && 
                                          item.compo_id != '0';
                        
                        if (hasCompoText) {
                            // We have compo_text - format and display it
                            const comboText = String(item.compo_text).trim();
                            
                            // Determine combo type from compo_text format
                            // BOGO format: "buy: X| get: Y"
                            // Tiered pricing format: "buy: X| for: Y"
                            
                            
                            itemHtml += `<div class="item-combo">
                                 ${comboText}
                            </div>`;
                        } else if (hasCompoId) {
                            // Fallback: show combo ID if compo_text is not available
                            // This should rarely happen as backend should generate compo_text
                            itemHtml += `<div class="item-combo">
                                COMBO ID: ${item.compo_id}
                            </div>`;
                        }
                        
                        if (item.discount && item.discount != '0' && item.discount != 0) {
                            itemHtml += `<div class="item-disc">${item.discount}% Discount</div>`;
                        }
                        
                        itemHtml += `
                        <div class="row-line"><span>Qty:</span> <span>${item.quantity || 0}</span></div>`;
                        
                        // Show combo_price if item has combo, otherwise show regular price
                        const hasCombo = (item.compo_id && item.compo_id != 0 && item.compo_id != '0') || 
                                        (item.combo_price && item.combo_price != null && item.combo_price != 0);
                        
                        if (hasCombo && item.combo_price != null && item.combo_price != 0) {
                            // Show combo price (effective price after combo is applied)
                            itemHtml += `<div class="row-line"><span>Price:</span> <span>${formatCurrency(item.combo_price)}</span></div>`;
                        } else {
                            // Show regular price
                            itemHtml += `<div class="row-line"><span>Price:</span> <span>${formatCurrency(item.price || 0)}</span></div>`;
                        }
                        
                        itemHtml += ``;
                        
                        if (item.discount && item.discount != '0' && item.discount != 0) {
                            itemHtml += `<div class="row-line"><span>Discount:</span> <span>${item.discount}%</span></div>`;
                        }
                        
                        const taxRate = item.tax || printData.totals?.tax_rate || 0;
                        const taxAmount = item.tax_amount || ((item.subtotal || 0) * (taxRate / 100));
                        
                        itemHtml += `
                        <div class="row-line"><span>Tax:</span> <span>${taxRate}%</span></div>
                        <div class="row-line"><span>Tax Amount:</span> <span>${formatCurrency(taxAmount)}</span></div>
                        <div class="row-line subtotal">
                            <span>Subtotal:</span> <span>${formatCurrency(item.subtotal || 0)}</span>
                        </div>
                    </div>`;
                        
                        return itemHtml;
                    }).join('');
                    
                    // Build totals HTML - matching printview.blade.php structure
                    const totals = printData.totals || {};
                    
                    // Build vouchers HTML - matching printview.blade.php structure
                    let vouchersHtml = '';
                    let totalVouchersAmount = 0;
                    
                    // Handle vouchers - can be array or object with voucher IDs as keys
                    if (printData.vouchers) {
                        let vouchersArray = [];
                        
                        // Check if vouchers is an array
                        if (Array.isArray(printData.vouchers) && printData.vouchers.length > 0) {
                            vouchersArray = printData.vouchers;
                        } 
                        // Check if vouchers is an object (key-value pairs where key is voucher ID)
                        else if (typeof printData.vouchers === 'object' && Object.keys(printData.vouchers).length > 0) {
                            // Convert object to array format
                            vouchersArray = Object.keys(printData.vouchers).map(voucherId => {
                                const voucherData = printData.vouchers[voucherId];
                                return {
                                    id: voucherId,
                                    amount: voucherData?.amount || voucherData || 0
                                };
                            });
                        }
                        
                        if (vouchersArray.length > 0) {
                            vouchersHtml = '<h3 class="section-title">Vouchers</h3>';
                            vouchersHtml += vouchersArray.map(voucher => {
                                const voucherId = voucher.id || voucher.voucher_id || 'N/A';
                                const amount = voucher.amount || voucher || 0;
                                const amountNum = parseFloat(amount) || 0;
                                totalVouchersAmount += amountNum;
                                
                                return `<div class="row-line">
                                    <span>Voucher ID ${voucherId}:</span> <span>${formatCurrency(amountNum)}</span>
                                </div>`;
                            }).join('');
                        }
                    }
                    
                    // Use calculated total vouchers amount if totals.vouchers_amount is not available
                    if (totalVouchersAmount > 0 && (!totals.vouchers_amount || totals.vouchers_amount == 0)) {
                        totals.vouchers_amount = totalVouchersAmount;
                    }
                    let totalsHtml = '<hr class="divider">';
                    
                    if (totals.subtotal) {
                        totalsHtml += `<div class="row-line"><span>Sub Total:</span> <span>${formatCurrency(totals.subtotal)}</span></div>`;
                    }
                    
                    if (totals.tax_amount && totals.tax_amount > 0 && totals.tax_amount != '0.00' && totals.tax_amount != '0') {
                        totalsHtml += `<div class="row-line"><span>Tax (${totals.tax_rate || 0}%):</span> <span>${formatCurrency(totals.tax_amount)}</span></div>`;
                    }
                    
                    if (totals.discount && totals.discount != '0.00' && totals.discount != '0') {
                        totalsHtml += `<div class="row-line total"><span>Discount:</span> <span>${formatCurrency(totals.discount)}</span></div>`;
                    }
                    
                    if (totals.vouchers_amount && totals.vouchers_amount > 0) {
                        totalsHtml += `<div class="row-line total"><span>Vouchers:</span> <span>${formatCurrency(totals.vouchers_amount)}</span></div>`;
                    }
                    
                    totalsHtml += `<div class="row-line total"><span>Total:</span> <span>${formatCurrency(totals.total || 0)}</span></div>`;
                    
                    // Calculate total payment from all payment methods
                    let totalPaymentAmount = 0;
                    if (printData.payment_methods && Array.isArray(printData.payment_methods) && printData.payment_methods.length > 0) {
                        totalPaymentAmount = printData.payment_methods.reduce((sum, pm) => {
                            const amount = parseFloat(pm.amount || 0);
                            return sum + (amount > 0 ? amount : 0);
                        }, 0);
                    }
                    
                    // Round to 2 decimal places
                    totalPaymentAmount = Math.round(totalPaymentAmount * 100) / 100;
                    
                    if (totalPaymentAmount > 0) {
                        totalsHtml += `<div class="row-line total"><span>Customer Pay:</span> <span>${formatCurrency(totalPaymentAmount)}</span></div>`;
                    }
                    
                    // Build payment methods HTML if available
                    let paymentMethodsHtml = '';
                    if (printData.payment_methods && Array.isArray(printData.payment_methods) && printData.payment_methods.length > 0) {
                        // Filter out payment methods with zero or negative amounts
                        const validPaymentMethods = printData.payment_methods.filter(pm => {
                            const amount = parseFloat(pm.amount || 0);
                            return amount > 0;
                        });
                        
                        if (validPaymentMethods.length > 0) {
                            paymentMethodsHtml = '<hr class="divider">';
                            paymentMethodsHtml += '<h3 class="section-title">Payment Methods</h3>';
                            validPaymentMethods.forEach(pm => {
                                const methodName = pm.name || 'Payment';
                                const methodAmount = parseFloat(pm.amount || 0);
                                paymentMethodsHtml += `<div class="row-line"><span>Customer Paid via ${methodName}:</span> <span>${formatCurrency(methodAmount)}</span></div>`;
                            });
                        }
                    }
                    
                    doc.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset="UTF-8">
                            <style>
                                @page { size: 80mm auto; margin: 0 !important; }
                                
                                * {
                                    -webkit-print-color-adjust: exact !important;
                                    print-color-adjust: exact !important;
                                }
                                
                                body {
                                    margin: 0 !important;
                                    padding: 0 !important;
                                    background: white !important;
                                    font-family: 'Courier New', Courier, monospace !important;
                                    font-size: 12px !important;
                                    line-height: 1.2 !important;
                                    color: #000 !important;
                                    font-weight: bold !important;
                                    width: 100% !important;
                                }
                                
                                * {
                                    color: #000 !important;
                                    font-weight: bold !important;
                                }
                                
                                #printarea {
                                    display: block !important;
                                    visibility: visible !important;
                                    width: 72mm !important;
                                    max-width: 72mm !important;
                                    margin: 0 auto !important;
                                    padding: 0 !important;
                                    background: white !important;
                                }
                                
                                .receipt {
                                    width: 72mm !important;
                                    max-width: 72mm !important;
                                    margin: 0 auto !important;
                                    padding: 2mm 1mm !important;
                                    background: white !important;
                                    font-family: 'Courier New', Courier, monospace !important;
                                    font-size: 12px !important;
                                    line-height: 1.2 !important;
                                    color: #000 !important;
                                    box-sizing: border-box !important;
                                }
                                
                                .title {
                                    font-size: 17px !important;
                                    margin: 3px 0 !important;
                                    font-weight: bold !important;
                                    line-height: 1.2 !important;
                                    text-align: center !important;
                                    color: #000 !important;
                                }
                                
                                .divider {
                                    border: none !important;
                                    border-top: 1px dashed #000 !important;
                                    margin: 4px 0 !important;
                                    height: 0 !important;
                                    width: 100% !important;
                                }
                                
                                .line, .row-line {
                                    font-size: 11px !important;
                                    line-height: 1.2 !important;
                                    margin: 2px 0 !important;
                                    display: flex !important;
                                    justify-content: space-between !important;
                                    align-items: flex-start !important;
                                    word-wrap: break-word !important;
                                    font-weight: bold !important;
                                    color: #000 !important;
                                }
                                
                                .box {
                                    font-size: 11px !important;
                                    padding: 4px 2px !important;
                                    margin: 3px 0 !important;
                                    border: 1px solid #000 !important;
                                    line-height: 1.2 !important;
                                    text-align: center !important;
                                    font-weight: bold !important;
                                    color: #000 !important;
                                }
                                
                                .box .cmp-name {
                                    font-size: 12px !important;
                                    font-weight: bold !important;
                                    color: #000 !important;
                                }
                                
                                .section-title {
                                    font-size: 12px !important;
                                    margin: 4px 0 3px 0 !important;
                                    font-weight: bold !important;
                                    line-height: 1.2 !important;
                                    text-align: left !important;
                                    color: #000 !important;
                                }
                                
                                .item-name {
                                    font-size: 11px !important;
                                    font-weight: bold !important;
                                    margin: 4px 0 2px 0 !important;
                                    line-height: 1.2 !important;
                                    color: #000 !important;
                                }
                                
                                .item-disc {
                                    font-size: 10px !important;
                                    margin: 1px 0 !important;
                                    line-height: 1.2 !important;
                                    font-weight: bold !important;
                                    color: #000 !important;
                                }
                                
                                .item-combo {
                                    font-size: 10px !important;
                                    margin: 2px 0 !important;
                                    padding: 2px 4px !important;
                                    line-height: 1.2 !important;
                                    font-weight: bold !important;
                                    color: #000 !important;
                                    border: 1px dashed #000 !important;
                                    text-align: center !important;
                                }
                                
                                .subtotal {
                                    font-size: 11px !important;
                                    font-weight: bold !important;
                                    margin: 3px 0 !important;
                                    color: #000 !important;
                                }
                                
                                .total {
                                    font-size: 12px !important;
                                    font-weight: bold !important;
                                    margin: 3px 0 !important;
                                    color: #000 !important;
                                }
                                
                                .return-policy {
                                    font-size: 10px !important;
                                    margin-top: 8px !important;
                                    margin-bottom: 6px !important;
                                    line-height: 1.3 !important;
                                    text-align: left !important;
                                    padding: 4px 0 !important;
                                    border-top: 1px dashed #000 !important;
                                }
                                
                                .return-policy p {
                                    margin: 3px 0 !important;
                                    font-size: 10px !important;
                                    font-weight: bold !important;
                                    color: #000 !important;
                                }
                                
                                .thank {
                                    font-size: 11px !important;
                                    margin-top: 6px !important;
                                    line-height: 1.2 !important;
                                    text-align: center !important;
                                    font-weight: bold !important;
                                    color: #000 !important;
                                }
                                
                                .item {
                                    margin: 2px 0 !important;
                                }
                                
                                .text-center {
                                    text-align: center !important;
                                    font-weight: bold !important;
                                    color: #000 !important;
                                }
                                
                                b, strong {
                                    font-weight: bold !important;
                                    color: #000 !important;
                                }
                                
                                h2, h3 {
                                    font-weight: bold !important;
                                    color: #000 !important;
                                }
                                
                                .company-logo {
                                    width: 100% !important;
                                    max-width: 60mm !important;
                                    height: auto !important;
                                    margin: 0 auto 4px auto !important;
                                    display: block !important;
                                    text-align: center !important;
                                }
                                
                                .company-logo img {
                                    max-width: 100% !important;
                                    height: auto !important;
                                    max-height: 30mm !important;
                                    object-fit: contain !important;
                                }
                                
                                .footer-info {
                                    font-size: 10px !important;
                                    margin-top: 4px !important;
                                    line-height: 1.2 !important;
                                    text-align: center !important;
                                    font-weight: bold !important;
                                    color: #000 !important;
                                }
                                
                                .row-line span {
                                    display: inline-block !important;
                                    max-width: 50% !important;
                                    word-wrap: break-word !important;
                                    font-weight: bold !important;
                                    color: #000 !important;
                                }
                                
                                .row-line span:first-child {
                                    flex: 0 0 45% !important;
                                    margin-right: 5px !important;
                                    text-align: left !important;
                                    font-weight: bold !important;
                                    color: #000 !important;
                                }
                                
                                .row-line span:last-child {
                                    flex: 0 0 50% !important;
                                    text-align: right !important;
                                    font-weight: bold !important;
                                    color: #000 !important;
                                }
                            </style>
                        </head>
                        <body>
                            <div id="printarea">
                                <div class="receipt">
                                    <!-- Header -->
                                    <div class="text-center">
                                        ${logoHtml}
                                        <h2 class="title">${printData.warehouse?.company_name || printData.settings?.company_name || 'Company Name'}</h2>
                                        <hr class="divider">
                                    </div>
                                    
                                    <!-- POS ID -->
                                    <div class="line">
                                        <b>${printData.pos_id || ''}</b>
                                    </div>
                                    
                                    <!-- Company Info -->
                                    ${companyInfo.length > 0 ? `<div class="box">${companyInfo.join('')}</div>` : ''}
                                    
                                    <!-- Company Registration Number (TRN) -->
                                    ${printData.settings?.registration_number ? `<div class="line"><b>TRN:</b> ${printData.settings.registration_number}</div>` : ''}
                                    
                                    <!-- Tax Invoice Title -->
                                    <div class="text-center" style="margin: 3px 0;">
                                        <div style="font-size: 13px !important; font-weight: bold !important; color: #000 !important; text-align: center !important;">Tax Invoice</div>
                                    </div>
                                    
                                    <!-- Customer -->
                                    ${customerInfo}
                                    <div class="line"><b>Date:</b> ${printData.date || ''}</div>
                                    
                                    ${printData.warehouse ? `<div class="line"><b>Warehouse:</b> ${printData.warehouse.name || ''}</div>` : ''}
                                    
                                    <h3 class="section-title">Items</h3>
                                    
                                    <!-- Items -->
                                    ${itemsHtml}
                                    
                                    <!-- Vouchers -->
                                    ${vouchersHtml}
                                    
                                    <!-- Totals -->
                                    ${totalsHtml}
                                    
                                    <!-- Payment Methods -->
                                    ${paymentMethodsHtml}
                                    
                                    <!-- Return Policy -->
                                    <div class="return-policy">
                                        <p>Items must be returned within 14 days for exchange or store credit, and they must be in their original condition with the receipt. Promotional products cannot be exchanged or returned.</p>
                                        <p>يجب إرجاع العناصر خلال 14 يومًا للتبديل أو الحصول على رصيد المتجر، ويجب أن تكون بحالتها الأصلية مع الفاتورة. لا يمكن استبدال أو إرجاع المنتجات الترويجية.</p>
                                    </div>
                                    
                                    <!-- Footer Info -->
                                    <div class="footer-info">
                                        ${(() => {
                                            let footerHtml = '';
                                            const settings = printData.settings || {};
                                            const warehouse = printData.warehouse || {};
                                            
                                            // Prioritize warehouse address, city, and country if available
                                            if (warehouse.address || warehouse.city || warehouse.country) {
                                                // Address - no comma before first item
                                                if (warehouse.address) {
                                                    footerHtml += `<span>${warehouse.address}</span>`;
                                                }
                                                
                                                // City - comma before
                                                if (warehouse.city) {
                                                    footerHtml += `<span>, ${warehouse.city}</span>`;
                                                }
                                                
                                                // Zipcode - " - " separator
                                                if (warehouse.city_zip) {
                                                    footerHtml += `<span> - ${warehouse.city_zip}</span>`;
                                                }
                                                
                                                // Country - comma before
                                                if (warehouse.country) {
                                                    footerHtml += `<span>, ${warehouse.country}</span>`;
                                                }
                                            } else {
                                                // Fallback to system company settings
                                                // Email (mail_from_address or company_email) - no comma before first item
                                                if (settings.company_email || settings.mail_from_address) {
                                                    footerHtml += `<span>${settings.company_email || settings.mail_from_address}</span>`;
                                                }
                                                
                                                // Address - comma before
                                                if (settings.company_address) {
                                                    footerHtml += `<span>, ${settings.company_address}</span>`;
                                                }
                                                
                                                // City - comma before
                                                if (settings.company_city) {
                                                    footerHtml += `<span>, ${settings.company_city}</span>`;
                                                }
                                                
                                                // State - comma before
                                                if (settings.company_state) {
                                                    footerHtml += `<span>, ${settings.company_state}</span>`;
                                                }
                                                
                                                // Zipcode - " - " separator
                                                if (settings.company_zipcode) {
                                                    footerHtml += `<span> - ${settings.company_zipcode}</span>`;
                                                }
                                                
                                                // Country - comma before
                                                if (settings.company_country) {
                                                    footerHtml += `<span>, ${settings.company_country}</span>`;
                                                }
                                            }
                                            
                                            // Telephone - comma before (always from settings)
                                            if (settings.company_telephone) {
                                                footerHtml += `<span>, ${settings.company_telephone}</span>`;
                                            }
                                            
                                            return footerHtml;
                                        })()}
                                    </div>
                                    
                                    <div class="thank text-center">
                                        Thank You For Shopping With Us.
                                    </div>
                                </div>
                            </div>
                        </body>
                        </html>
                    `);
                    doc.close();
                    
                    // Wait for content to load, then print
                    iframe.onload = function() {
                        setTimeout(() => {
                            iframe.contentWindow.print();
                            
                            // Listen for afterprint event to know when print dialog is closed
                            iframe.contentWindow.addEventListener('afterprint', function() {
                                // Remove iframe after printing
                                setTimeout(() => {
                                    if (document.body.contains(iframe)) {
                                        document.body.removeChild(iframe);
                                    }
                                    resolve();
                                }, 500);
                            }, { once: true });
                            
                            // Fallback: if afterprint doesn't fire, resolve after a delay
                            setTimeout(() => {
                                if (document.body.contains(iframe)) {
                                    document.body.removeChild(iframe);
                                }
                                resolve();
                            }, 5000);
                        }, 100);
                    };
                    
                    // Handle iframe load error
                    iframe.onerror = function() {
                        if (document.body.contains(iframe)) {
                            document.body.removeChild(iframe);
                        }
                        reject(new Error('Failed to load print content'));
                    };
                } catch (error) {
                    reject(error);
                }
            });
        }
        
        // Clear all other pending jobs except the specified one
        async function clearOtherJobs(keepJobId) {
            const jobs = await getPendingJobs();
            const otherJobs = jobs.filter(j => j.id !== keepJobId);
            
            if (otherJobs.length === 0) {
                return;
            }
            
            log(`Clearing ${otherJobs.length} other pending job(s)...`, 'info');
            
            for (const job of otherJobs) {
                try {
                    // Mark as failed with a message indicating it was cleared automatically
                    await markJobFailed(job.id, 'Cleared automatically - another job is being processed');
                    processedJobs.delete(job.id);
                    // Remove from queue if present
                    const queueIndex = processingQueue.findIndex(j => j.id === job.id);
                    if (queueIndex > -1) {
                        processingQueue.splice(queueIndex, 1);
                    }
                } catch (error) {
                    // Silently continue - don't log errors for auto-clearing
                    console.warn(`Failed to clear job ${job.id}:`, error);
                }
            }
            
            // Refresh jobs list after clearing
            await updateJobsList();
        }
        
        // Process a print job
        async function processJob(job) {
            if (processedJobs.has(job.id)) {
                return; // Already processing or processed
            }
            
            processedJobs.add(job.id);
            log(`Processing job ${job.id} (POS: ${job.reference_id || 'N/A'})`, 'info');
            
            // Clear all other pending jobs automatically
            await clearOtherJobs(job.id);
            
            try {
                // Print using browser print dialog and wait for it to complete
                await printReceipt(job.print_data);
                
                // Mark as complete after print dialog is closed
                const success = await markJobComplete(job.id);
                if (success) {
                    log(`Job ${job.id} completed successfully`, 'success');
                    processedJobs.delete(job.id);
                    updateJobsList();
                } else {
                    log(`Failed to mark job ${job.id} as complete`, 'error');
                    processedJobs.delete(job.id);
                }
            } catch (error) {
                log(`Error processing job ${job.id}: ${error.message}`, 'error');
                await markJobFailed(job.id, error.message);
                processedJobs.delete(job.id);
            }
        }
        
        // Process the queue - only one job at a time
        async function processQueue() {
            if (isProcessingQueue || processingQueue.length === 0) {
                return;
            }
            
            // Don't auto-process if disabled
            if (!autoProcessEnabled) {
                return;
            }
            
            isProcessingQueue = true;
            
            // Process only the first job in queue (one at a time)
            if (processingQueue.length > 0 && autoProcessEnabled) {
                const job = processingQueue.shift();
                
                // Skip if already processed
                if (!processedJobs.has(job.id)) {
                    // Process the job - it will automatically clear other jobs
                    await processJob(job);
                }
            }
            
            isProcessingQueue = false;
        }
        
        // Update jobs list
        async function updateJobsList() {
            const jobs = await getPendingJobs();
            allJobs = jobs; // Store for manual operations
            
            if (jobs.length === 0) {
                jobsList.innerHTML = '<div style="text-align: center; color: #9ca3af; padding: 20px;">No pending jobs</div>';
                return;
            }
            
            jobsList.innerHTML = jobs.map(job => {
                const isProcessing = processedJobs.has(job.id);
                const canPrint = !isProcessing && (job.status === 'pending' || !job.status);
                
                return `
                <div class="job-item ${job.status || 'pending'}" data-job-id="${job.id}">
                    <div class="job-header">
                        <div class="job-id">Job #${job.id} - ${job.reference_id || 'N/A'}</div>
                        <div class="job-status ${job.status || 'pending'}">${(job.status || 'pending').toUpperCase()}</div>
                    </div>
                    <div class="job-details">
                        Printer: ${job.printer_ip || 'N/A'}:${job.printer_port || '9100'}<br>
                        Created: ${new Date(job.created_at).toLocaleString()}
                        ${job.print_data?.pos_id ? `<br>Receipt: ${job.print_data.pos_id}` : ''}
                    </div>
                    ${canPrint ? `
                        <div style="margin-top: 10px;">
                            <button class="btn" onclick="printJobNow(${job.id})" style="padding: 6px 12px; font-size: 12px; width: 100%;">
                                Print Now
                            </button>
                        </div>
                    ` : isProcessing ? `
                        <div style="margin-top: 10px; text-align: center; color: #3b82f6; font-size: 12px;">
                            Processing...
                        </div>
                    ` : ''}
                </div>
            `;
            }).join('');
        }
        
        // Print a specific job immediately
        async function printJobNow(jobId) {
            // Refresh jobs list first to get latest data
            await updateJobsList();
            
            const job = allJobs.find(j => j.id === jobId);
            if (!job) {
                log(`Job ${jobId} not found`, 'error');
                await updateJobsList(); // Refresh to show current state
                return;
            }
            
            if (processedJobs.has(jobId)) {
                log(`Job ${jobId} is already being processed`, 'info');
                return;
            }
            
            log(`Manually printing job ${jobId} (POS: ${job.reference_id || 'N/A'})`, 'info');
            await processJob(job);
            // Refresh jobs list after processing
            await updateJobsList();
        }
        
        // Clear all pending jobs from queue
        async function clearQueue() {
            if (!confirm('Are you sure you want to clear all pending jobs from the queue? This will mark them as failed.')) {
                return;
            }
            
            const serverUrl = serverUrlInput.value.trim();
            const token = apiTokenInput.value.trim();
            
            if (!serverUrl || !token) {
                log('Server URL and API Token are required', 'error');
                return;
            }
            
            const jobs = await getPendingJobs();
            if (jobs.length === 0) {
                log('No jobs to clear', 'info');
                return;
            }
            
            log(`Clearing ${jobs.length} job(s) from queue...`, 'info');
            
            let cleared = 0;
            let failed = 0;
            const errors = [];
            
            for (const job of jobs) {
                try {
                    const success = await markJobFailed(job.id, 'Cleared by user');
                    if (success) {
                        cleared++;
                        processedJobs.delete(job.id);
                        // Remove from queue if present
                        const queueIndex = processingQueue.findIndex(j => j.id === job.id);
                        if (queueIndex > -1) {
                            processingQueue.splice(queueIndex, 1);
                        }
                    } else {
                        failed++;
                        errors.push(`Job ${job.id}: Failed to mark as failed`);
                    }
                } catch (error) {
                    failed++;
                    const errorMsg = `Job ${job.id}: ${error.message}`;
                    errors.push(errorMsg);
                    log(errorMsg, 'error');
                }
            }
            
            if (cleared > 0) {
                log(`Successfully cleared ${cleared} job(s)`, 'success');
            }
            if (failed > 0) {
                log(`Failed to clear ${failed} job(s). Check logs for details.`, 'error');
                if (errors.length > 0) {
                    console.error('Clear queue errors:', errors);
                }
            }
            
            // Refresh the jobs list
            await updateJobsList();
        }
        
        // Toggle auto-processing (currently disabled - manual only)
        function toggleAutoProcess() {
            autoProcessEnabled = autoProcessToggle.checked;
            if (autoProcessEnabled) {
                log('Auto-processing enabled - jobs will be processed automatically', 'info');
                // Process queue if there are jobs
                if (processingQueue.length > 0 && !isProcessingQueue) {
                    processQueue();
                }
            } else {
                log('Auto-processing disabled - use "Print Now" button to print jobs manually', 'info');
            }
        }
        
        // Poll for jobs - only updates the list, no automatic processing
        async function pollForJobs() {
            if (!isRunning) return;
            
            const jobs = await getPendingJobs();
            
            // Just update the list - no automatic processing
            // User must click "Print Now" to print jobs
            if (jobs.length > 0) {
                // Only log if it's a new job (not already in our list)
                const newJobs = jobs.filter(j => !allJobs.find(aj => aj.id === j.id));
                if (newJobs.length > 0) {
                    log(`Found ${newJobs.length} new pending job(s) - click "Print Now" to print`, 'info');
                }
            }
            
            // Always update the jobs list to show current state
            await updateJobsList();
        }
        
        // Start service
        function startService() {
            const serverUrl = serverUrlInput.value.trim();
            const token = apiTokenInput.value.trim();
            
            if (!serverUrl || !token) {
                alert('Please enter Server URL and API Token');
                return;
            }
            
            saveConfig();
            isRunning = true;
            updateStatus('active', 'Running');
            startBtn.disabled = true;
            stopBtn.disabled = false;
            
            log('Print service started', 'success');
            log(`Polling every ${pollIntervalInput.value} seconds`, 'info');
            
            // Poll immediately
            pollForJobs();
            
            // Then poll at interval
            const interval = parseInt(pollIntervalInput.value) * 1000;
            pollingInterval = setInterval(pollForJobs, interval);
        }
        
        // Stop service
        function stopService() {
            isRunning = false;
            updateStatus('inactive', 'Stopped');
            startBtn.disabled = false;
            stopBtn.disabled = true;
            
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
            
            log('Print service stopped', 'info');
        }
        
        // Initialize
        loadConfig();
        log('Print service ready. Configure and click "Start Service" to begin.', 'info');
        
        // Auto-start if config is saved
        const saved = localStorage.getItem('printServiceConfig');
        if (saved) {
            const config = JSON.parse(saved);
            if (config.autoStart) {
                setTimeout(startService, 1000);
            }
        }
    </script>
</body>
</html>

