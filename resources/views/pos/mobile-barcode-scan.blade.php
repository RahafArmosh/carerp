<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Mobile Barcode Scan') }} - {{ config('app.name', 'ERP') }}</title>
    
    <!-- Bootstrap CSS with CDN fallback -->
    <link href="{{ asset('public/css/bootstrap.min.css') }}" rel="stylesheet" onerror="this.onerror=null; this.href='https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css';">
    <!-- Font Awesome with CDN fallback -->
    <link href="{{ asset('public/css/font-awesome.min.css') }}" rel="stylesheet" onerror="this.onerror=null; this.href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';">
    <!-- Tabler Icons with CDN fallback -->
    <link href="{{ asset('public/css/tabler-icons.min.css') }}" rel="stylesheet" onerror="this.onerror=null; this.href='https://cdn.jsdelivr.net/npm/@tabler/icons@latest/icons-sprite.svg';">
    
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 10px;
            margin: 0;
        }
        
        .mobile-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 20px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .scan-section {
            padding: 30px 20px;
            background: #f8f9fa;
        }
        
        .barcode-input-wrapper {
            position: relative;
            margin-bottom: 20px;
        }
        
        .barcode-input {
            width: 100%;
            padding: 18px 50px 18px 20px;
            font-size: 18px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            background: white;
            transition: all 0.3s;
        }
        
        .barcode-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .scan-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 24px;
        }
        
        .btn-scan {
            width: 100%;
            padding: 16px;
            font-size: 18px;
            font-weight: 600;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-scan:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-scan:active {
            transform: translateY(0);
        }
        
        .btn-scan:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .product-info {
            padding: 25px 20px;
            display: none;
        }
        
        .product-info.show {
            display: block;
        }
        
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .info-card h3 {
            margin: 0 0 15px 0;
            font-size: 20px;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 14px;
        }
        
        .info-value {
            color: #333;
            font-size: 15px;
            text-align: right;
            flex: 1;
            margin-left: 15px;
        }
        
        .quantity-badge {
            display: inline-block;
            padding: 6px 12px;
            background: #667eea;
            color: white;
            border-radius: 20px;
            font-weight: 600;
            font-size: 16px;
        }
        
        .location-badge {
            display: inline-block;
            padding: 6px 12px;
            background: #10b981;
            color: white;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .custom-fields-section {
            margin-top: 15px;
        }
        
        .custom-field-item {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .custom-field-label {
            font-weight: 600;
            color: #666;
            font-size: 13px;
            margin-bottom: 5px;
        }
        
        .custom-field-value {
            color: #333;
            font-size: 15px;
        }
        
        .note-section {
            margin-top: 20px;
        }
        
        .note-textarea {
            width: 100%;
            min-height: 100px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            resize: vertical;
            transition: all 0.3s;
        }
        
        .note-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-save-note {
            width: 100%;
            padding: 14px;
            margin-top: 15px;
            font-size: 16px;
            font-weight: 600;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-save-note:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 12px;
            margin: 15px 20px;
            display: none;
            text-align: center;
            font-weight: 500;
        }
        
        .error-message.show {
            display: block;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
            color: #667eea;
        }
        
        .loading.show {
            display: block;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        #scanner-container {
            width: 100%;
            max-width: 100%;
            min-height: 300px;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            margin: 0 auto;
        }
        
        #scanner-container video,
        #scanner-container canvas,
        #scanner-container img {
            width: 100% !important;
            height: auto !important;
            max-width: 100%;
            display: block;
        }
        
        /* Ensure html5-qrcode elements are visible */
        #scanner-container #qr-shaded-region,
        #scanner-container #qr-shaded-region * {
            box-sizing: border-box;
        }
        
        #scanner-status {
            margin-top: 15px;
            font-size: 14px;
        }
        
        #scanned-result {
            margin-top: 15px;
            padding: 12px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="mobile-container">
        <div class="header">
            <h1><i class="ti ti-scan"></i> {{ __('Barcode Scanner') }}</h1>
            <p>{{ __('Scan or enter barcode to view product details') }}</p>
        </div>
        
        <div class="scan-section">
            <div class="barcode-input-wrapper">
                <input type="text" 
                       id="barcode-input" 
                       class="barcode-input" 
                       placeholder="{{ __('Scan or type barcode here...') }}"
                       autocomplete="off"
                       autofocus>
                <i class="ti ti-barcode scan-icon"></i>
            </div>
            
            <button type="button" class="btn-scan" id="scan-btn" style="margin-bottom: 15px;">
                <i class="ti ti-scan"></i>
                <span>{{ __('Search Product') }}</span>
            </button>
            
            <button type="button" class="btn-scan" id="camera-scan-btn" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <i class="ti ti-camera"></i>
                <span>{{ __('Open Camera Scanner') }}</span>
            </button>
            
            <!-- Camera Scanner Section -->
            <div id="camera-scanner-section" style="display: none; margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 12px;">
                <div class="text-center mb-3">
                    <div id="scanner-container" style="position: relative; display: block; width: 100%; max-width: 100%; min-height: 300px; background: #000; border-radius: 12px; overflow: hidden; margin: 0 auto;"></div>
                    <div id="scanner-status" class="mt-2">
                        <p class="text-muted">{{ __('Position barcode in front of camera') }}</p>
                    </div>
                    <div id="scanned-result" class="alert alert-success mt-2" style="display: none;">
                        <strong>{{ __('Scanned:') }}</strong> <span id="scanned-barcode-value"></span>
                    </div>
                </div>
                <button type="button" class="btn-scan" id="stop-camera-btn" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                    <i class="ti ti-x"></i>
                    <span>{{ __('Stop Camera') }}</span>
                </button>
            </div>
            
            <div class="error-message" id="error-message"></div>
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>{{ __('Searching product...') }}</p>
            </div>
        </div>
        
        <div class="product-info" id="product-info">
            <div class="info-card">
                <h3><i class="ti ti-package"></i> {{ __('Product Information') }}</h3>
                <div class="info-row">
                    <span class="info-label">{{ __('Product Name') }}:</span>
                    <span class="info-value" id="product-name">-</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('SKU') }}:</span>
                    <span class="info-value" id="product-sku">-</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('Category') }}:</span>
                    <span class="info-value" id="product-category">-</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('Brand') }}:</span>
                    <span class="info-value" id="product-brand">-</span>
                </div>
            </div>
            
            <div class="info-card">
                <h3><i class="ti ti-box"></i> {{ __('Stock Information') }}</h3>
                <div id="sub-products-list">
                    <!-- Sub-products will be dynamically added here -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery - Load from CDN first for reliability -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" 
            integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" 
            crossorigin="anonymous"
            onerror="this.onerror=null; this.src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js';"></script>
    <!-- Local jQuery fallback -->
    <script>
        // If jQuery still not loaded after 1 second, try local file
        setTimeout(function() {
            if (typeof jQuery === 'undefined' && typeof window.$ === 'undefined') {
                console.warn('jQuery CDN failed, trying local file...');
                var localScript = document.createElement('script');
                localScript.src = '{{ asset('public/js/jquery.min.js') }}';
                localScript.onerror = function() {
                    console.error('All jQuery sources failed to load');
                };
                document.head.appendChild(localScript);
            }
        }, 1000);
    </script>
    
    <!-- html5-qrcode library for barcode scanning -->
    <script>
        // Load html5-qrcode library with fallback
        (function() {
            var script = document.createElement('script');
            script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
            script.onerror = function() {
                console.warn('Failed to load html5-qrcode from unpkg, trying jsdelivr...');
                var fallbackScript = document.createElement('script');
                fallbackScript.src = 'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js';
                fallbackScript.onerror = function() {
                    console.error('Failed to load html5-qrcode from all CDNs. Camera scanning will not be available, but manual search will still work.');
                };
                document.head.appendChild(fallbackScript);
            };
            document.head.appendChild(script);
        })();
    </script>
    <script>
        // Wait for both jQuery and Html5Qrcode to be loaded
        let jqueryWaitAttempts = 0;
        const maxJqueryWaitAttempts = 50; // 5 seconds max (50 * 100ms)
        
        function waitForjQueryAndInit() {
            jqueryWaitAttempts++;
            if (typeof jQuery === 'undefined' || typeof window.$ === 'undefined') {
                if (jqueryWaitAttempts < maxJqueryWaitAttempts) {
                    console.log('Waiting for jQuery to load... (attempt ' + jqueryWaitAttempts + ')');
                    setTimeout(waitForjQueryAndInit, 100);
                } else {
                    console.error('jQuery failed to load after ' + maxJqueryWaitAttempts + ' attempts. Please check your internet connection or refresh the page.');
                    // Show error to user
                    document.body.innerHTML = '<div style="padding: 20px; text-align: center;"><h2>Error Loading Page</h2><p>jQuery library failed to load. Please check your internet connection and refresh the page.</p><button onclick="location.reload()" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">Refresh Page</button></div>';
                }
                return;
            }
            
            console.log('jQuery loaded successfully, initializing mobile scanner...');
            initMobileScanner();
        }
        
        function initMobileScanner() {
            // Ensure jQuery is available
            if (typeof jQuery === 'undefined' || typeof window.$ === 'undefined') {
                console.error('jQuery still not available');
                return;
            }
            
            // Wait for Html5Qrcode if not loaded yet
            if (typeof Html5Qrcode === 'undefined') {
                console.log('Html5Qrcode library not loaded yet, will retry...');
                // Don't block - continue with manual search functionality
            }
            
            // Use jQuery's document ready
            jQuery(document).ready(function($) {
                console.log('jQuery document ready, setting up event handlers...');
            const barcodeInput = $('#barcode-input');
            const scanBtn = $('#scan-btn');
            const productInfo = $('#product-info');
            const errorMessage = $('#error-message');
            const loading = $('#loading');
            let currentProductId = null;
            let processing = false;
            
            // Camera scanner variables - define early so they can be used anywhere
            let html5QrCode = null;
            let cameraScanning = false;
            const cameraScanBtn = $('#camera-scan-btn');
            const stopCameraBtn = $('#stop-camera-btn');
            const cameraScannerSection = $('#camera-scanner-section');
            const scannerContainer = $('#scanner-container');
            const scannerStatus = $('#scanner-status');
            const scannedResult = $('#scanned-result');
            const scannedBarcodeValue = $('#scanned-barcode-value');
            
            // Check if barcode parameter exists in URL (from stock report scan)
            const urlParams = new URLSearchParams(window.location.search);
            const barcodeParam = urlParams.get('barcode');
            
            if (barcodeParam) {
                // Set barcode in input and auto-search (don't open camera)
                barcodeInput.val(barcodeParam);
                cameraScannerSection.hide(); // Hide camera section
                setTimeout(function() {
                    scanProduct();
                }, 300); // Small delay to ensure page is fully loaded
            } else {
                // Optional: Auto-start camera scanner when page loads (no barcode in URL)
                // But ensure manual search is always available
                // Wait for Html5Qrcode library to be available
                let libraryCheckAttempts = 0;
                const maxLibraryCheckAttempts = 25; // 5 seconds max (25 * 200ms)
                
                function waitForLibraryAndStart() {
                    libraryCheckAttempts++;
                    if (typeof Html5Qrcode !== 'undefined' && typeof Html5Qrcode.getCameras === 'function') {
                        console.log('Html5Qrcode library ready, attempting to start camera...');
                        // Try to start camera, but don't block if it fails
                        setTimeout(function() {
                            startCameraScanner().catch(function(err) {
                                console.warn('Camera auto-start failed, but manual search is available:', err);
                                // Ensure input is visible if camera fails
                                $('.barcode-input-wrapper').show();
                                scanBtn.show();
                            });
                        }, 500);
                    } else if (libraryCheckAttempts < maxLibraryCheckAttempts) {
                        console.log('Waiting for Html5Qrcode library... (attempt ' + libraryCheckAttempts + ')');
                        // Check again after a short delay
                        setTimeout(waitForLibraryAndStart, 200);
                    } else {
                        console.warn('Html5Qrcode library failed to load after multiple attempts. Manual search still available.');
                        // Ensure input section is visible
                        $('.barcode-input-wrapper').show();
                        scanBtn.show();
                        // Optionally show camera scan button if user wants to try manually
                        cameraScanBtn.show();
                    }
                }
                // Start checking after a short delay
                setTimeout(waitForLibraryAndStart, 300);
            }
            
            // Handle Enter key in barcode input
            barcodeInput.on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    scanProduct();
                }
            });
            
            // Handle scan button click
            scanBtn.on('click', function() {
                scanProduct();
            });
            
            // Handle paste event (for barcode scanners)
            barcodeInput.on('paste', function(e) {
                setTimeout(function() {
                    scanProduct();
                }, 100);
            });
            
            // Ensure scanner status element exists
            if (scannerStatus.length === 0) {
                console.error('Scanner status element not found');
            }
            
            // Ensure input and search button are always visible and functional
            // This ensures manual search works even if camera fails
            $('.barcode-input-wrapper').show();
            scanBtn.show();
            
            // Check if camera scan button exists
            if (cameraScanBtn.length === 0) {
                console.error('Camera scan button not found!');
            } else {
                console.log('Camera scan button found (ID: camera-scan-btn), setting up click handler');
                // Show camera scan button so users can manually open camera scanner
                cameraScanBtn.show();
                
                // Verify button is visible and clickable
                console.log('Camera scan button visible:', cameraScanBtn.is(':visible'));
                console.log('Camera scan button enabled:', !cameraScanBtn.prop('disabled'));
                
                // Open camera scanner - use event delegation to ensure it works
                cameraScanBtn.off('click').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Camera scan button clicked');
                    
                    if (cameraScanning) {
                        console.log('Camera already scanning, ignoring click');
                        return false;
                    }
                    
                    // Always handle promise from async function
                    startCameraScanner()
                        .then(function() {
                            console.log('Camera scanner started successfully');
                        })
                        .catch(function(err) {
                            console.error('Error starting camera scanner:', err);
                            const errorMsg = err && err.message ? err.message : '{{ __("Failed to start camera scanner. Please try again.") }}';
                            showError(errorMsg);
                            // Ensure button is visible and enabled
                            cameraScanBtn.prop('disabled', false).show();
                            $('.barcode-input-wrapper').show();
                            scanBtn.show();
                        });
                    return false;
                });
            }
            
            // Stop camera scanner
            stopCameraBtn.on('click', function() {
                stopCameraScanner();
            });
            
            async function startCameraScanner() {
                if (cameraScanning) {
                    console.log('Camera already scanning, skipping...');
                    return Promise.resolve();
                }
                
                console.log('Starting camera scanner...');
                
                try {
                    // Check if Html5Qrcode is available
                    if (!window.Html5Qrcode) {
                        console.error('Html5Qrcode library not available');
                        const errorMsg = '<p class="text-danger"><i class="ti ti-alert-circle"></i> {{ __("Barcode scanner library not loaded. Please refresh the page.") }}</p>';
                        if (scannerStatus.length > 0) {
                            scannerStatus.html(errorMsg);
                        } else {
                            showError('{{ __("Barcode scanner library not loaded. Please refresh the page.") }}');
                        }
                        if (cameraScanBtn.length > 0) {
                            cameraScanBtn.prop('disabled', false).html('<i class="ti ti-camera"></i> <span>{{ __("Open Camera Scanner") }}</span>').show();
                        }
                        $('.barcode-input-wrapper').show();
                        scanBtn.show();
                        return Promise.reject(new Error('Html5Qrcode library not loaded'));
                    }
                    
                    console.log('Html5Qrcode library found, proceeding...');
                    
                    if (cameraScanBtn.length > 0) {
                        cameraScanBtn.prop('disabled', true).html('<i class="ti ti-loader"></i> {{ __("Loading...") }}');
                    }
                    if (cameraScannerSection.length > 0) {
                        cameraScannerSection.show();
                    }
                    if (scannerStatus.length > 0) {
                        scannerStatus.html('<p class="text-info"><i class="ti ti-loader"></i> {{ __("Detecting cameras...") }}</p>');
                    }
                    
                    // Hide input section and camera scan button when camera is active
                    $('.barcode-input-wrapper').hide();
                    scanBtn.hide();
                    cameraScanBtn.hide();
                    
                    // Ensure scanner container exists and is visible
                    const container = document.getElementById('scanner-container');
                    if (!container) {
                        console.error('Scanner container element not found');
                        const errorMsg = '<p class="text-danger"><i class="ti ti-alert-circle"></i> {{ __("Scanner container not found") }}</p>';
                        if (scannerStatus.length > 0) {
                            scannerStatus.html(errorMsg);
                        }
                        if (cameraScanBtn.length > 0) {
                            cameraScanBtn.prop('disabled', false).html('<i class="ti ti-camera"></i> <span>{{ __("Open Camera Scanner") }}</span>').show();
                        }
                        $('.barcode-input-wrapper').show();
                        scanBtn.show();
                        return Promise.reject(new Error('Scanner container not found'));
                    }
                    
                    console.log('Scanner container found, clearing and initializing...');
                    
                    // Clear any previous scanner instance
                    container.innerHTML = '';
                    
                    // Create Html5Qrcode instance
                    console.log('Creating Html5Qrcode instance for scanner-container');
                    try {
                        html5QrCode = new Html5Qrcode('scanner-container');
                        console.log('Html5Qrcode instance created successfully');
                    } catch (err) {
                        console.error('Error creating Html5Qrcode instance:', err);
                        const errorMsg = '<p class="text-danger"><i class="ti ti-alert-circle"></i> {{ __("Error initializing scanner:") }} ' + (err.message || err) + '</p>';
                        if (scannerStatus.length > 0) {
                            scannerStatus.html(errorMsg);
                        }
                        if (cameraScanBtn.length > 0) {
                            cameraScanBtn.prop('disabled', false).html('<i class="ti ti-camera"></i> <span>{{ __("Open Camera Scanner") }}</span>').show();
                        }
                        $('.barcode-input-wrapper').show();
                        scanBtn.show();
                        return Promise.reject(new Error('Error creating Html5Qrcode instance: ' + (err.message || err)));
                    }
                    
                    // Get available cameras
                    console.log('Getting available cameras...');
                    const cameras = await Html5Qrcode.getCameras();
                    console.log('Available cameras:', cameras);
                    
                    if (cameras.length === 0) {
                        scannerStatus.html('<p class="text-danger"><i class="ti ti-alert-circle"></i> {{ __("No camera found. Please ensure your device has a camera and grant camera permissions.") }}</p>');
                        cameraScanBtn.prop('disabled', false).html('<i class="ti ti-camera"></i> <span>{{ __("Open Camera Scanner") }}</span>').show();
                        // Show input section again
                        $('.barcode-input-wrapper').show();
                        scanBtn.show();
                        return Promise.reject(new Error('No camera found'));
                    }
                    
                    // Prefer rear camera
                    let cameraId = cameras[0].id;
                    const rearCamera = cameras.find(cam => 
                        cam.label.toLowerCase().includes('back') || 
                        cam.label.toLowerCase().includes('rear') ||
                        cam.label.toLowerCase().includes('environment')
                    );
                    if (rearCamera) {
                        cameraId = rearCamera.id;
                    }
                    
                    scannerStatus.html('<p class="text-info"><i class="ti ti-loader"></i> {{ __("Starting camera...") }}</p>');
                    
                    console.log('Starting camera with ID:', cameraId);
                    
                    // Start scanning with better configuration for mobile
                    await html5QrCode.start(
                        cameraId,
                        {
                            fps: 10,
                            qrbox: function(viewfinderWidth, viewfinderHeight) {
                                // Use 80% of viewfinder for scanning area on mobile
                                let minEdgePercentage = 0.8;
                                let minEdgeSize = Math.min(viewfinderWidth, viewfinderHeight);
                                let qrboxSize = Math.floor(minEdgeSize * minEdgePercentage);
                                console.log('QR box size:', qrboxSize, 'Viewfinder:', viewfinderWidth, 'x', viewfinderHeight);
                                return {
                                    width: qrboxSize,
                                    height: qrboxSize
                                };
                            },
                            aspectRatio: 1.0
                        },
                        (decodedText, decodedResult) => {
                            // Barcode detected
                            const barcode = decodedText.trim();
                            if (barcode && !processing) {
                                scannedBarcodeValue.text(barcode);
                                scannedResult.show();
                                scannerStatus.html('<p class="text-success"><i class="ti ti-check"></i> {{ __("Barcode detected!") }}</p>');
                                
                                // Set barcode in input
                                barcodeInput.val(barcode);
                                
                                // Stop camera and search
                                if (html5QrCode && cameraScanning) {
                                    html5QrCode.stop().then(() => {
                                        html5QrCode.clear();
                                        cameraScanning = false;
                                        
                                        // Hide camera section and show input
                                        cameraScannerSection.hide();
                                        scannedResult.hide();
                                        $('.barcode-input-wrapper').show();
                                        scanBtn.show();
                                        
                                        // Show camera scan button again so user can scan another barcode
                                        cameraScanBtn.prop('disabled', false).html('<i class="ti ti-camera"></i> <span>{{ __("Open Camera Scanner") }}</span>').show();
                                        
                                        // Search for the product
                                        scanProduct();
                                    }).catch((err) => {
                                        console.error('Error stopping camera:', err);
                                        // Even if stop fails, continue with search
                                        cameraScanning = false;
                                        cameraScannerSection.hide();
                                        $('.barcode-input-wrapper').show();
                                        scanBtn.show();
                                        
                                        // Show camera scan button again so user can scan another barcode
                                        cameraScanBtn.prop('disabled', false).html('<i class="ti ti-camera"></i> <span>{{ __("Open Camera Scanner") }}</span>').show();
                                        
                                        scanProduct();
                                    });
                                } else {
                                    // Camera already stopped, just search
                                    // Ensure camera scan button is visible
                                    cameraScanBtn.prop('disabled', false).html('<i class="ti ti-camera"></i> <span>{{ __("Open Camera Scanner") }}</span>').show();
                                    scanProduct();
                                }
                            }
                        },
                        (errorMessage) => {
                            // Ignore common scanning errors
                            if (errorMessage && 
                                !errorMessage.includes('NotFoundException') && 
                                !errorMessage.includes('No QR code') &&
                                !errorMessage.includes('No barcode detected')) {
                                console.log('Scanning error:', errorMessage);
                            }
                        }
                    );
                    
                    cameraScanning = true;
                    scannerStatus.html('<p class="text-success"><i class="ti ti-camera"></i> {{ __("Camera ready. Position barcode in front of camera.") }}</p>');
                    // Camera scan button is already hidden when camera is active
                    
                    return Promise.resolve();
                    
                } catch (error) {
                    console.error('Camera scanner error:', error);
                    let errorMsg = error.message || '{{ __("Unknown error") }}';
                    
                    // Handle specific error cases
                    if (error.name === 'NotAllowedError' || error.message.includes('permission')) {
                        errorMsg = '{{ __("Camera permission denied. Please allow camera access and try again.") }}';
                    } else if (error.name === 'NotFoundError' || error.message.includes('camera')) {
                        errorMsg = '{{ __("No camera found. Please ensure your device has a camera.") }}';
                    } else if (error.message) {
                        errorMsg = '{{ __("Error starting camera:") }} ' + error.message;
                    }
                    
                    scannerStatus.html('<p class="text-danger"><i class="ti ti-alert-circle"></i> ' + errorMsg + '</p>');
                    cameraScanBtn.prop('disabled', false).html('<i class="ti ti-camera"></i> <span>{{ __("Open Camera Scanner") }}</span>').show();
                    cameraScanning = false;
                    
                    // Show input section again on error
                    $('.barcode-input-wrapper').show();
                    scanBtn.show();
                    
                    // Return rejected promise so caller can handle error
                    return Promise.reject(error);
                }
            }
            
            function stopCameraScanner() {
                if (html5QrCode && cameraScanning) {
                    html5QrCode.stop().then(() => {
                        html5QrCode.clear();
                        cameraScanning = false;
                        cameraScannerSection.hide();
                        scannedResult.hide();
                        cameraScanBtn.prop('disabled', false).html('<i class="ti ti-camera"></i> <span>{{ __("Open Camera Scanner") }}</span>');
                        
                        // Show input section and camera scan button again
                        $('.barcode-input-wrapper').show();
                        scanBtn.show();
                        cameraScanBtn.show();
                    }).catch((err) => {
                        console.error('Error stopping camera:', err);
                        cameraScanning = false;
                        cameraScannerSection.hide();
                        cameraScanBtn.prop('disabled', false).html('<i class="ti ti-camera"></i> <span>{{ __("Open Camera Scanner") }}</span>');
                        
                        // Show input section and camera scan button again
                        $('.barcode-input-wrapper').show();
                        scanBtn.show();
                        cameraScanBtn.show();
                    });
                } else {
                    cameraScanning = false;
                    cameraScannerSection.hide();
                    cameraScanBtn.prop('disabled', false).html('<i class="ti ti-camera"></i> <span>{{ __("Open Camera Scanner") }}</span>');
                    
                    // Show input section and camera scan button again
                    $('.barcode-input-wrapper').show();
                    scanBtn.show();
                    cameraScanBtn.show();
                }
            }
            
            // Stop camera when page is hidden/unloaded
            $(window).on('beforeunload', function() {
                stopCameraScanner();
            });
            
            // Define scanProduct function early so it can be called from anywhere
            function scanProduct() {
                const barcode = barcodeInput.val().trim();
                
                console.log('scanProduct called with barcode:', barcode);
                
                if (!barcode) {
                    showError('{{ __("Please enter or scan a barcode") }}');
                    return;
                }
                
                if (processing) {
                    console.log('Already processing, skipping...');
                    return;
                }
                
                processing = true;
                hideError();
                showLoading();
                hideProductInfo();
                
                console.log('Making AJAX request to:', '{{ route("pos.barcode.mobile-scan.search") }}');
                
                $.ajax({
                    url: '{{ route("pos.barcode.mobile-scan.search") }}',
                    method: 'POST',
                    data: {
                        barcode: barcode,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        console.log('AJAX success response:', response);
                        if (response && response.success) {
                            displayProductInfo(response);
                            // Clear input and refocus for next scan
                            barcodeInput.val('');
                            setTimeout(function() {
                                barcodeInput.focus();
                            }, 100);
                            
                            // Ensure camera scan button is visible after successful scan
                            cameraScanBtn.prop('disabled', false).html('<i class="ti ti-camera"></i> <span>{{ __("Open Camera Scanner") }}</span>').show();
                        } else {
                            const errorMsg = (response && response.error) ? response.error : '{{ __("Product not found") }}';
                            console.error('Product not found:', errorMsg);
                            showError(errorMsg);
                            
                            // Ensure camera scan button is visible even if product not found
                            cameraScanBtn.prop('disabled', false).html('<i class="ti ti-camera"></i> <span>{{ __("Open Camera Scanner") }}</span>').show();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', {
                            status: status,
                            error: error,
                            responseText: xhr.responseText,
                            statusCode: xhr.status
                        });
                        let errorMsg = '{{ __("Error searching product") }}';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMsg = xhr.responseJSON.error;
                        } else if (xhr.status === 404) {
                            errorMsg = '{{ __("Product not found") }}';
                        } else if (xhr.status === 403) {
                            errorMsg = '{{ __("Permission denied") }}';
                        } else if (xhr.status === 500) {
                            errorMsg = '{{ __("Server error. Please try again later.") }}';
                        } else if (xhr.status === 0) {
                            errorMsg = '{{ __("Network error. Please check your connection.") }}';
                        }
                        showError(errorMsg);
                        
                        // Ensure camera scan button is visible even on error
                        cameraScanBtn.prop('disabled', false).html('<i class="ti ti-camera"></i> <span>{{ __("Open Camera Scanner") }}</span>').show();
                    },
                    complete: function() {
                        processing = false;
                        hideLoading();
                        
                        // Ensure camera scan button is always visible after request completes
                        cameraScanBtn.prop('disabled', false).html('<i class="ti ti-camera"></i> <span>{{ __("Open Camera Scanner") }}</span>').show();
                    }
                });
            }
            
            function displayProductInfo(data) {
                // Product Information (common for all sub-products)
                $('#product-name').text(data.product.name || '-');
                $('#product-sku').text(data.product.sku || '-');
                $('#product-category').text(data.category.name || '-');
                $('#product-brand').text(data.brand.name || '-');
                
                // Clear previous sub-products list
                $('#sub-products-list').empty();
                
                // Display each sub-product as a row
                if (data.sub_products && data.sub_products.length > 0) {
                    data.sub_products.forEach(function(subProduct, index) {
                        const price = parseFloat(subProduct.sale_price || 0);
                        const customFieldsHtml = subProduct.custom_fields && subProduct.custom_fields.length > 0
                            ? subProduct.custom_fields.map(function(field) {
                                return `<div class="custom-field-item">
                                    <div class="custom-field-label">${field.name}:</div>
                                    <div class="custom-field-value">${field.value || '-'}</div>
                                </div>`;
                            }).join('')
                            : '<div class="text-muted">{{ __("No custom fields") }}</div>';
                        
                        const subProductHtml = `
                            <div class="info-card sub-product-row" data-sub-product-id="${subProduct.id}">
                                <div class="info-row">
                                    <span class="info-label">{{ __('Barcode') }}:</span>
                                    <span class="info-value">${subProduct.product_no || '-'}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">{{ __('Quantity') }}:</span>
                                    <span class="info-value">
                                        <span class="quantity-badge">${subProduct.quantity || '0'}</span>
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">{{ __('Location') }}:</span>
                                    <span class="info-value">
                                        <span class="location-badge">${subProduct.warehouse.name || '-'}</span>
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">{{ __('Sale Price') }}:</span>
                                    <span class="info-value">${price.toFixed(2)} {{ __("AED") }}</span>
                                </div>
                                ${customFieldsHtml ? `<div class="custom-fields-section" style="margin-top: 15px;">
                                    <div class="custom-field-label" style="font-weight: 600; margin-bottom: 10px; color: #666;">{{ __('Custom Fields') }}:</div>
                                    ${customFieldsHtml}
                                </div>` : ''}
                                <div class="note-section" style="margin-top: 15px;">
                                    <label class="custom-field-label" style="font-weight: 600; margin-bottom: 8px;">{{ __('Note') }}:</label>
                                    <textarea 
                                        class="note-textarea sub-product-note" 
                                        data-sub-product-id="${subProduct.id}"
                                        placeholder="{{ __('Add a note about this product...') }}"
                                        rows="3">${subProduct.note || ''}</textarea>
                                    <button type="button" class="btn-save-note sub-product-save-note" data-sub-product-id="${subProduct.id}" style="margin-top: 10px;">
                                        <i class="ti ti-device-floppy"></i> {{ __('Save Note') }}
                                    </button>
                                </div>
                            </div>
                        `;
                        
                        $('#sub-products-list').append(subProductHtml);
                    });
                } else {
                    $('#sub-products-list').html('<div class="empty-state"><p>{{ __("No stock found") }}</p></div>');
                }
                
                // Show product info
                showProductInfo();
            }
            
            function showProductInfo() {
                productInfo.addClass('show');
                // Scroll to product info
                $('html, body').animate({
                    scrollTop: productInfo.offset().top - 20
                }, 300);
            }
            
            function hideProductInfo() {
                productInfo.removeClass('show');
            }
            
            function showError(message) {
                errorMessage.text(message);
                errorMessage.addClass('show');
            }
            
            function hideError() {
                errorMessage.removeClass('show');
            }
            
            function showLoading() {
                loading.addClass('show');
                scanBtn.prop('disabled', true);
            }
            
            function hideLoading() {
                loading.removeClass('show');
                scanBtn.prop('disabled', false);
            }
            
            // Save note functionality - delegated event handler for dynamically added buttons
            $(document).on('click', '.sub-product-save-note', function() {
                const btn = $(this);
                const subProductId = btn.data('sub-product-id');
                const noteTextarea = btn.closest('.sub-product-row').find('.sub-product-note');
                const note = noteTextarea.val().trim();
                
                if (!subProductId) {
                    showError('{{ __("Sub-product ID not found") }}');
                    return;
                }
                
                // Disable button during save
                btn.prop('disabled', true).html('<i class="ti ti-loader"></i> {{ __("Saving...") }}');
                
                $.ajax({
                    url: '{{ route("pos.barcode.save-note") }}',
                    method: 'POST',
                    data: {
                        sub_product_id: subProductId,
                        note: note,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success feedback
                            btn.html('<i class="ti ti-check"></i> {{ __("Saved") }}').css('background', '#10b981');
                            setTimeout(function() {
                                btn.html('<i class="ti ti-device-floppy"></i> {{ __("Save Note") }}').css('background', '');
                                btn.prop('disabled', false);
                            }, 2000);
                        } else {
                            showError(response.error || '{{ __("Error saving note") }}');
                            btn.prop('disabled', false).html('<i class="ti ti-device-floppy"></i> {{ __("Save Note") }}');
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = '{{ __("Error saving note") }}';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMsg = xhr.responseJSON.error;
                        }
                        showError(errorMsg);
                        btn.prop('disabled', false).html('<i class="ti ti-device-floppy"></i> {{ __("Save Note") }}');
                    }
                });
            });
            
            // Prevent form submission on Enter+Ctrl in note textarea
            $(document).on('keydown', '.sub-product-note', function(e) {
                if (e.key === 'Enter' && e.ctrlKey) {
                    e.preventDefault();
                    $(this).closest('.sub-product-row').find('.sub-product-save-note').click();
                }
            });
            }); // End of jQuery(document).ready
        } // End of initMobileScanner
        
        // Initialize when page loads - wait for jQuery first
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', waitForjQueryAndInit);
        } else {
            waitForjQueryAndInit();
        }
    </script>
</body>
</html>
