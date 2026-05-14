<?php
/**
 * Local Print Service for POS Receipt Printing
 * 
 * This script runs on the POS computer (same network as printer) and polls
 * the server for pending print jobs, then prints them directly to the Epson printer.
 * 
 * SETUP INSTRUCTIONS:
 * 1. Install PHP on the POS computer (if not already installed)
 * 2. Install composer and run: composer require mike42/escpos-php
 * 3. Copy this file to the POS computer
 * 4. Update the configuration below
 * 5. Run this script continuously (use Windows Task Scheduler or a service manager)
 * 
 * For Windows Task Scheduler:
 * - Create a new task
 * - Trigger: "At startup" or "On a schedule" (every 5 seconds)
 * - Action: Start a program
 * - Program: php.exe
 * - Arguments: "C:\path\to\local_print_service.php"
 * - Start in: C:\path\to\
 * 
 * For Linux (systemd service):
 * - Create /etc/systemd/system/pos-print.service
 * - Enable and start the service
 */

require __DIR__ . '/vendor/autoload.php';

use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;

// ==================== CONFIGURATION ====================
$config = [
    // Server URL (where your Laravel application is hosted)
    'server_url' => 'https://your-server.com', // Change this!
    
    // API Token (must match PRINT_AGENT_TOKEN in .env on server)
    'api_token' => 'default-token-change-me', // Change this!
    
    // Polling interval in seconds
    'poll_interval' => 3, // Check for jobs every 3 seconds
    
    // Printer IP (usually auto-detected from job, but can set default here)
    'default_printer_ip' => '10.255.254.17',
    'default_printer_port' => 9100,
    
    // Logging
    'log_file' => __DIR__ . '/print_service.log',
    'log_level' => 'INFO', // DEBUG, INFO, ERROR
];

// ==================== FUNCTIONS ====================

function logMessage($message, $level = 'INFO') {
    global $config;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message\n";
    file_put_contents($config['log_file'], $logEntry, FILE_APPEND);
    
    if ($level === 'ERROR' || $config['log_level'] === 'DEBUG') {
        echo $logEntry;
    }
}

function getPendingJobs($serverUrl, $token) {
    $url = rtrim($serverUrl, '/') . '/api/print-jobs/pending';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Print-Service-Token: ' . $token,
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Set to true in production with valid SSL
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        logMessage("CURL Error: $error", 'ERROR');
        return null;
    }
    
    if ($httpCode !== 200) {
        logMessage("HTTP Error: $httpCode", 'ERROR');
        return null;
    }
    
    $data = json_decode($response, true);
    if (!$data || !isset($data['success']) || !$data['success']) {
        logMessage("API Error: " . ($data['message'] ?? 'Unknown error'), 'ERROR');
        return null;
    }
    
    return $data['jobs'] ?? [];
}

function markJobComplete($serverUrl, $token, $jobId) {
    $url = rtrim($serverUrl, '/') . '/api/print-jobs/' . $jobId . '/complete';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Print-Service-Token: ' . $token,
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        logMessage("Job $jobId marked as completed");
        return true;
    } else {
        logMessage("Failed to mark job $jobId as completed (HTTP $httpCode)", 'ERROR');
        return false;
    }
}

function markJobFailed($serverUrl, $token, $jobId, $errorMessage) {
    $url = rtrim($serverUrl, '/') . '/api/print-jobs/' . $jobId . '/fail';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['error_message' => $errorMessage]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Print-Service-Token: ' . $token,
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        logMessage("Job $jobId marked as failed: $errorMessage");
        return true;
    } else {
        logMessage("Failed to mark job $jobId as failed (HTTP $httpCode)", 'ERROR');
        return false;
    }
}

function printReceipt($printData, $printerIp, $printerPort) {
    try {
        logMessage("Connecting to printer at $printerIp:$printerPort");
        $connector = new NetworkPrintConnector($printerIp, $printerPort);
        $printer = new Printer($connector);
        
        $data = $printData;
        $settings = $data['settings'] ?? [];
        $totals = $data['totals'] ?? [];
        
        // Print header
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setTextSize(2, 2);
        $printer->text(($settings['company_name'] ?? 'Company Name') . "\n");
        $printer->setTextSize(1, 1);
        $printer->feed();
        
        // Company address
        if (!empty($settings['company_address'])) {
            $printer->text($settings['company_address'] . "\n");
        }
        if (!empty($settings['company_city']) || !empty($settings['company_state']) || !empty($settings['company_zipcode'])) {
            $address = trim(($settings['company_city'] ?? '') . ' ' . ($settings['company_state'] ?? '') . ' ' . ($settings['company_zipcode'] ?? ''));
            if (!empty($address)) {
                $printer->text($address . "\n");
            }
        }
        if (!empty($settings['company_country'])) {
            $printer->text($settings['company_country'] . "\n");
        }
        if (!empty($settings['company_telephone'])) {
            $printer->text("Tel: " . $settings['company_telephone'] . "\n");
        }
        
        $printer->feed();
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text(str_repeat("-", 42) . "\n");
        
        // POS ID and Date
        $printer->setEmphasis(true);
        $printer->text("Receipt #: " . ($data['pos_id'] ?? '') . "\n");
        $printer->setEmphasis(false);
        $printer->text("Date: " . ($data['date'] ?? '') . "\n");
        $printer->text(str_repeat("-", 42) . "\n");
        
        // Customer info
        if (!empty($data['customer'])) {
            $customer = $data['customer'];
            $printer->text("Customer: " . ($customer['name'] ?? '') . "\n");
            if (!empty($customer['billing_phone'])) {
                $printer->text("Phone: " . $customer['billing_phone'] . "\n");
            }
            if (!empty($customer['billing_address'])) {
                $printer->text("Address: " . $customer['billing_address'] . "\n");
            }
        } else {
            $printer->text("Customer: Walk-in Customer\n");
        }
        
        if (!empty($data['warehouse'])) {
            $printer->text("Warehouse: " . ($data['warehouse']['name'] ?? '') . "\n");
        }
        
        $printer->text(str_repeat("-", 42) . "\n");
        $printer->feed();
        
        // Items
        $printer->setEmphasis(true);
        $printer->text("ITEMS\n");
        $printer->setEmphasis(false);
        $printer->text(str_repeat("-", 42) . "\n");
        
        foreach ($data['items'] ?? [] as $item) {
            $itemName = substr($item['name'] ?? '', 0, 20);
            $quantity = $item['quantity'] ?? 0;
            $price = $item['price'] ?? 0;
            $discount = $item['discount'] ?? 0;
            $subtotal = $item['subtotal'] ?? 0;
            $taxRate = $item['tax'] ?? 0;
            
            $printer->text($itemName . "\n");
            $printer->text(sprintf("Qty: %-5s Price: %.2f\n", $quantity, $price));
            
            if (!empty($discount) && $discount != '0' && $discount != 0) {
                $printer->text("Discount: " . $discount . "%\n");
            }
            
            if ($taxRate > 0) {
                $tax = $subtotal * ($taxRate / 100);
                $printer->text("Tax: " . $taxRate . "% (" . number_format($tax, 2) . ")\n");
            }
            
            $printer->setEmphasis(true);
            $printer->text("Subtotal: " . number_format($subtotal, 2) . "\n");
            $printer->setEmphasis(false);
            $printer->feed(1);
        }
        
        // Vouchers
        if (!empty($data['vouchers']) && count($data['vouchers']) > 0) {
            $printer->text(str_repeat("-", 42) . "\n");
            $printer->setEmphasis(true);
            $printer->text("VOUCHERS\n");
            $printer->setEmphasis(false);
            foreach ($data['vouchers'] as $voucher) {
                $amount = $voucher['amount'] ?? 0;
                $printer->text("Amount: " . number_format($amount, 2) . "\n");
            }
        }
        
        // Totals
        $printer->text(str_repeat("-", 42) . "\n");
        $printer->setEmphasis(true);
        $printer->text("TOTALS\n");
        $printer->setEmphasis(false);
        $printer->text(str_repeat("-", 42) . "\n");
        
        $printer->text("Sub Total: " . number_format($totals['subtotal'] ?? 0, 2) . "\n");
        
        if (($totals['tax_rate'] ?? 0) > 0) {
            $printer->text("Tax (" . ($totals['tax_rate'] ?? 0) . "%): " . number_format($totals['tax_amount'] ?? 0, 2) . "\n");
        }
        
        if (($totals['discount'] ?? 0) > 0) {
            $printer->text("Discount: " . number_format($totals['discount'] ?? 0, 2) . "\n");
        }
        
        if (($totals['vouchers_amount'] ?? 0) > 0) {
            $printer->text("Vouchers: " . number_format($totals['vouchers_amount'] ?? 0, 2) . "\n");
        }
        
        $printer->setTextSize(1, 2);
        $printer->setEmphasis(true);
        $printer->text("TOTAL: " . number_format($totals['total'] ?? 0, 2) . "\n");
        $printer->setTextSize(1, 1);
        $printer->setEmphasis(false);
        
        if (($totals['customer_pay'] ?? 0) > 0) {
            $printer->text("Customer Pay: " . number_format($totals['customer_pay'] ?? 0, 2) . "\n");
        }
        
        if (($totals['customer_return'] ?? 0) != 0) {
            $printer->text("Return: " . number_format($totals['customer_return'] ?? 0, 2) . "\n");
        }
        
        $printer->feed(2);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Thank You For Shopping With Us!\n");
        $printer->feed(3);
        
        // Cut paper
        $printer->cut();
        $printer->close();
        
        logMessage("Receipt printed successfully");
        return true;
    } catch (\Exception $e) {
        logMessage("Print error: " . $e->getMessage(), 'ERROR');
        throw $e;
    }
}

// ==================== MAIN LOOP ====================

logMessage("Local Print Service started");
logMessage("Server URL: " . $config['server_url']);
logMessage("Polling interval: " . $config['poll_interval'] . " seconds");

while (true) {
    try {
        $jobs = getPendingJobs($config['server_url'], $config['api_token']);
        
        if ($jobs === null) {
            // Error occurred, wait before retrying
            sleep($config['poll_interval']);
            continue;
        }
        
        if (empty($jobs)) {
            // No jobs, wait and check again
            sleep($config['poll_interval']);
            continue;
        }
        
        logMessage("Found " . count($jobs) . " pending job(s)");
        
        foreach ($jobs as $job) {
            $jobId = $job['id'];
            $printerIp = $job['printer_ip'] ?? $config['default_printer_ip'];
            $printerPort = $job['printer_port'] ?? $config['default_printer_port'];
            $printData = $job['print_data'];
            
            logMessage("Processing job $jobId (POS: " . ($job['reference_id'] ?? 'N/A') . ")");
            
            try {
                printReceipt($printData, $printerIp, $printerPort);
                markJobComplete($config['server_url'], $config['api_token'], $jobId);
                logMessage("Job $jobId completed successfully");
            } catch (\Exception $e) {
                $errorMsg = $e->getMessage();
                logMessage("Job $jobId failed: $errorMsg", 'ERROR');
                markJobFailed($config['server_url'], $config['api_token'], $jobId, $errorMsg);
            }
        }
        
        // Small delay between processing jobs
        usleep(500000); // 0.5 seconds
        
    } catch (\Exception $e) {
        logMessage("Main loop error: " . $e->getMessage(), 'ERROR');
        sleep($config['poll_interval']);
    }
}

