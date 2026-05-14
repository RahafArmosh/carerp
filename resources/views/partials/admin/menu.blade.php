<!-- Load jQuery first -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"
    integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous">
</script>
<!-- Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js"
    integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous">
</script>


<style>
    @import "https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700";

    body {
        font-family: 'Poppins', sans-serif;
        background: #FCFCFC;
    }

    p {
        font-family: 'Poppins', sans-serif;
        font-size: 1.1em;
        font-weight: 300;
        line-height: 1.7em;
        color: #999;
    }

    a:hover {
        background-color: #EFF0F2;
    }

    .navbar {
        padding: 15px 10px;
        background: #FCFCFC;
        border: none;
        border-radius: 0;
        margin-bottom: 40px;
        box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
    }

    .navbar-btn {
        box-shadow: none;
        outline: none !important;
        border: none;
    }

    .line {
        width: 100%;
        height: 1px;
        border-bottom: 1px dashed #ddd;
        margin: 40px 0;
    }

    /* Sidebar Style */
    .text-sidebar {
        color: #000000;
    }

    .wrapper {
        display: flex;
        width: 100%;
        align-items: stretch;
    }

    /* Sidebar base (some theme CSS is more specific than #sidebar, so we also
       override using body.motor-deal-shell #sidebar.sidebar below). */
    #sidebar {
        min-width: 255px;
        max-width: 255px;
        width: 255px;
        background: linear-gradient(180deg, #ffffff 0%, #f6f8ff 100%);
        color: #0f172a;
        left: 0 !important;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        transform: translateX(-255px) !important; /* Hide by default */
        margin-left: 0 !important; /* avoid conflicting legacy rules */
        position: fixed !important; /* Overlay content */
        z-index: 9999 !important; /* Above all content including account setup */
        border-right: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 10px 0 30px rgba(15, 23, 42, 0.08);
    }

    /* Force WHITE sidebar even when motor theme is loaded (more specific selector). */
    body.motor-deal-shell #sidebar.sidebar,
    body.motor-deal-shell #sidebar.sidebar.light-sidebar,
    body.motor-deal-shell #sidebar.collapse {
        background: #ffffff !important;
        color: #0f172a !important;
        border-right: 1px solid rgba(15, 23, 42, 0.08) !important;
        box-shadow: 10px 0 30px rgba(15, 23, 42, 0.08) !important;
    }

    body.motor-deal-shell #sidebar .dash-navbar > .dash-item > .dash-link,
    body.motor-deal-shell #sidebar ul.dash-navbar .dash-link {
        color: #0f172a !important;
    }

    body.motor-deal-shell #sidebar ul.dash-navbar .dash-item:hover > .dash-link {
        background: rgba(37, 99, 235, 0.08) !important;
        color: #0b2a6f !important;
    }

    body.motor-deal-shell #sidebar ul.dash-navbar .dash-item.active > .dash-link,
    body.motor-deal-shell #sidebar ul.dash-navbar .dash-trigger.active > .dash-link {
        background: rgba(37, 99, 235, 0.14) !important;
        color: #0b2a6f !important;
        box-shadow: inset 2px 0 0 #2563eb !important;
    }

    #sidebar:not(.active) {
        transform: translateX(0) !important; /* Show when active class is removed */
    }

    #sidebar.active {
        transform: translateX(-255px) !important; /* Hide when active class is present */
    }

    #sidebar .sidebar-header {
        padding: 20px;
        background: rgba(255, 255, 255, 0.92);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* Close button (X icon) styling */
    #sidebar-close-btn {
        position: absolute;
        top: 12px;
        right: 12px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(15, 23, 42, 0.12);
        font-size: 20px;
        color: #0f172a;
        cursor: pointer;
        padding: 0;
        line-height: 1;
        z-index: 10002; /* above overlay + header stacking contexts */
        transition: color 0.2s ease, background 0.2s ease, transform 0.15s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        pointer-events: auto;
    }

    #sidebar-close-btn:hover {
        color: #ffffff;
        background: #2563eb;
        transform: scale(1.1);
    }

    #sidebar-close-btn:active {
        transform: scale(0.95);
    }

    #sidebar ul.components {
        padding: 20px 0;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06) !important;
    }

    #sidebar ul p {
        color: #334155;
        padding: 10px;
    }

    #sidebar ul li a {
        padding: 10px;
        font-size: 1.1em;
        display: block;
        transition: background 0.3s;
    }

    a[data-toggle="collapse"] {
        position: relative;
    }

    a {
        color: #0f172a;
    }

    #sidebar ul li a:hover,
    #sidebar ul li a:focus {
        background: rgba(37, 99, 235, 0.08);
        color: #0b2a6f;
    }

    #sidebar ul li.active > a,
    #sidebar ul li a.active {
        background: rgba(37, 99, 235, 0.14);
        color: #0b2a6f;
        font-weight: 600;
    }

    .dropdown-toggle::after {
        display: block;
        position: absolute;
        top: 50%;
        right: 20px;
        transform: translateY(-50%);
    }

    ul ul a {
        font-size: 0.9em !important;
        padding-left: 30px !important;
    }

    ul.CTAs {
        padding: 20px;
    }

    ul.CTAs a {
        text-align: center;
        font-size: 0.9em !important;
        display: block;
        border-radius: 5px;
        margin-bottom: 5px;
    }

    a.download {
        background: rgba(37, 99, 235, 0.12);
        color: #0b2a6f;
    }

    a.article,
    a.article:hover {
        background: #FCFCFC !important;
        color: #0f172a !important;
    }

    /* Content Style */
    #content {
        width: 100%;
        padding: 20px;
        min-height: 100vh;
        transition: all 0.3s;
    }

    /* Overlay styling — do not cover the 255px sidebar column so the close (X)
       and nav always receive real clicks (avoids z-index / stacking edge cases). */
    #sidebar-overlay {
        position: fixed !important;
        top: 0 !important;
        bottom: 0 !important;
        inset-inline-start: 255px !important;
        inset-inline-end: 0 !important;
        width: auto !important;
        height: 100% !important;
        background-color: rgba(0, 0, 0, 0.5) !important;
        /* Semi-transparent black */
        z-index: 9998 !important;
        /* Below sidebar (9999) but above all other content */
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
        pointer-events: none; /* Allow clicks to pass through when hidden */
    }

    /* Mobile/tablet: let overlay cover full screen so outside-click always works. */
    @media (max-width: 1024px) {
        #sidebar-overlay {
            inset-inline-start: 0 !important;
        }
    }

    /* When sidebar is NOT active (visible), show the overlay */
    #sidebar:not(.active) + #sidebar-overlay,
    body.sidebar-open #sidebar-overlay {
        opacity: 1 !important;
        visibility: visible !important;
        pointer-events: auto !important; /* Enable clicks when visible */
        display: block !important;
    }

    /* Force full width layout - sidebar overlays content */
    .dash-container {
        margin-left: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        padding-left: 20px !important; /* Add left padding */
        padding-top: 70px !important; /* Account for fixed header height */
        top: 0 !important; /* Remove any top positioning */
    }

    .dash-content {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        padding-left: 20px !important; /* Add left padding */
    }

    /* Ensure header also takes full width and proper positioning */
    .dash-header {
        /* position: fixed !important; */
        width: 100% !important;
        left: 0 !important;
        right: 0 !important;
        top: 0 !important;
        margin-left: 0 !important;
        z-index: 1025 !important; /* Default z-index when sidebar is closed */
        transition: z-index 0.3s ease;
    }

    /* Lower z-index when sidebar menu is open (sidebar not active = visible) */
    #sidebar:not(.active) ~ .dash-header,
    body.sidebar-open .dash-header {
        z-index: 998 !important; /* Below sidebar (1001) when menu is open */
    }

    /* Make hamburger menu icon always visible and clickable */
    .mob-hamburger,
    .dash-header .dash-h-item.mob-hamburger {
display: flex;
    align-items: center;
    vertical-align: middle;
        visibility: visible !important;
        z-index: 1030 !important; /* Above header (1025) to ensure clickability */
        position: relative !important;
    }

    /* Ensure hamburger link is clickable */
    #mobile-collapse,
    .dash-header .dash-h-item.mob-hamburger .dash-head-link {
        z-index: 1031 !important;
        position: relative !important;
        pointer-events: auto !important;
        cursor: pointer !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-width: 50px !important;
        min-height: 50px !important;
        padding: 10px !important;
    }

    /* Ensure hamburger icon and all its children are clickable */
    .dash-header .dash-h-item.mob-hamburger .hamburger,
    .dash-header .dash-h-item.mob-hamburger .hamburger-box,
    .dash-header .dash-h-item.mob-hamburger .hamburger-inner {
        pointer-events: auto !important;
        cursor: pointer !important;
    }

    /* Media Queries */
    @media (max-width: 768px) {
        #sidebarCollapse span {
            display: none;
        }
    }

    .menu-item {
        display: block;
        padding: 10px 15px;
        color: #333;
        text-decoration: none;
        transition: background-color 0.3s ease-in-out;
    }

    .menu-item:hover {
        background-color: rgba(128, 128, 128, 0.1);
    }

    .dash-micon {
        /* margin-right: 10px; */
        /* background-color: #fff; */
        /* box-shadow: -3px 4px 23px rgba(0, 0, 0, 0.1); */
        /* margin-right: 15px; */
        /* border-radius: 12px; */
        height: 30px;
        width: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        vertical-align: middle;
    }

    .dash-mtext {
        font-size: 13px;
    }

    .dash-sidebar.light-sidebar .dash-navbar>.dash-item>.dash-link {
        border-radius: 12px;
        padding: 7px 10px 7px 7px;
    }

    .dash-submenu.show {
        display: block;
        /* Show the active submenu */
    }
</style>


<style>
    .dash-item.dash-hasmenu {
        list-style-type: none;
    }

    .dash-item.dash-hasmenu::before {
        content: "";
    }

    .dash-navbar {
        padding-left: 0px;
        max-height: 1000px;
        /* Adjust the value as needed */
    }

    /* Base styles for submenus */
    .dash-submenu {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 0;
        transform: translateY(-10px);
        will-change: max-height, opacity, transform;
        list-style: none;
    }

    /* Show submenus when parent is open */
    .dash-item.open>.dash-submenu {
        max-height: 2000px;
        /* Large enough to accommodate all content */
        opacity: 1;
        transform: translateY(0);
    }

    /* Nested submenus need a slight delay for a cascading effect */
    .dash-submenu .dash-submenu {
        transition-delay: 0.05s;
    }

    /* Add a smooth rotation to dropdown arrows if you have them */
    .dropdown-toggle::after {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .dash-item.open>.dropdown-toggle::after {
        transform: rotate(180deg);
    }

    /* Optional: Add a subtle hover effect to menu items */
    .dash-item>a:hover,
    .dash-item>.dropdown-toggle:hover {
        transition: background-color 0.2s ease;
    }
</style>
@php
    use App\Models\Utility;
    $setting = \App\Models\Utility::settings();
    $logo = \App\Models\Utility::get_file('uploads/logo/');

    $company_logo = $setting['company_logo_dark'] ?? '';
    $company_logos = $setting['company_logo_light'] ?? '';
    $company_small_logo = $setting['company_small_logo'] ?? '';

    $emailTemplate = \App\Models\EmailTemplate::emailTemplateData();
    $lang = Auth::user()->lang;

    $userPlan = \App\Models\Plan::getPlan(\Auth::user()->show_dashboard());

    /**
     * When true: hide HRM, CRM, Project, CRM notifications, Support, Zoom, Messenger,
     * notification templates, company "Setup Subscription Plan" / "Order", super-admin Plan / Plan Request / Order,
     * and client project/support entries. Set MOTOR_HIDE_SIDEBAR_EXTRAS=false in .env to show them again.
     */
    $motorHideSidebarExtras = filter_var(env('MOTOR_HIDE_SIDEBAR_EXTRAS', true), FILTER_VALIDATE_BOOLEAN);
@endphp

<!-- https://bootstrapious.com/p/bootstrap-sidebar -->


<!-- Sidebar  -->
@if (isset($setting['cust_theme_bg']) && $setting['cust_theme_bg'] == 'on')
    {{-- Do not use Bootstrap .collapse on #sidebar: it sets display:none on small screens while dash.js only toggles .active (margin offset). --}}
    <nav id="sidebar"
        class="sidebar light-sidebar active motor-sidebar bg-white"
        style="width: 255px; position: fixed; top: 0; bottom: 0; z-index: 9999 !important; overflow: auto">
    @else
        <nav id="sidebar" class="sidebar light-sidebar active motor-sidebar"
            style="width: 255px; position: fixed; top: 0; bottom: 0; z-index: 9999 !important; overflow: auto">
@endif
<div class="navbar-wrapper">
    <div class="sidebar-header m-header main-logo">

        <a href="#" class="b-brand">
            {{-- <img src="{{ asset(Storage::url('uploads/logo/'.$logo)) }}" alt="{{ env('APP_NAME') }}"
                        class="logo logo-lg" /> --}}

            <img src="{{ asset(config('app.brand_logo')) }}"
                alt="{{ config('app.name', 'AutoCore') }}" class="logo logo-lg"
                style="height: auto; width: 200px;">

        </a>
        <button id="sidebar-close-btn" type="button" aria-label="Close sidebar">
            <i class="ti ti-x"></i>
        </button>

    </div>
    <div class="navbar-content">
        @if (\Auth::user()->type != 'client')
            <ul class="dash-navbar">
                <!--------------------- Start Dashboard ----------------------------------->
                @if (
                    (!$motorHideSidebarExtras &&
                        (Gate::check('show hrm dashboard') ||
                            Gate::check('show project dashboard') ||
                            Gate::check('show crm dashboard'))) ||
                        Gate::check('show account dashboard'))
                    <li
                        class="dash-item dash-hasmenu
                                    {{ Request::segment(1) == 'account-dashboard' ||
                                    Request::segment(1) == 'stock-overview' ||
                                    Request::segment(1) == 'sell-overview' ||
                                    Request::segment(1) == 'income report' ||
                                    Request::segment(1) == 'reports-monthly-cashflow' ||
                                    Request::segment(1) == 'reports-quarterly-cashflow' ||
                                    Request::segment(1) == 'reports-payroll' ||
                                    Request::segment(1) == 'reports-leave' ||
                                    Request::segment(1) == 'reports-monthly-attendance' ||
                                    Request::segment(1) == 'reports-lead' ||
                                    Request::segment(1) == 'reports-deal' ||
                                    Request::segment(1) == 'reports-warehouse' ||
                                    Request::segment(1) == 'reports-daily-purchase' ||
                                    Request::segment(1) == 'reports-monthly-purchase' ||
                                    Request::route()->getName() == 'report.customer.statement' ||
                                    Request::route()->getName() == 'report.vendor.statement' ||
                                    Request::route()->getName() == 'report.employee.statement' ||
                                    Request::route()->getName() == 'report.invoice.summary' ||
                                    Request::route()->getName() == 'report.sales' ||
                                    Request::route()->getName() == 'subproduct.sell_report' ||
                                    Request::route()->getName() == 'subproduct.stock_movement_report' ||
                                    Request::route()->getName() == 'report.receivables' ||
                                    Request::route()->getName() == 'report.account.statement' ||
                                    Request::route()->getName() == 'report.payables' ||
                                    Request::route()->getName() == 'report.bill.summary' ||
                                    Request::route()->getName() == 'report.product.stock.report' ||
                                    Request::route()->getName() == 'report.item.master' ||
                                    Request::route()->getName() == 'transaction.index' ||
                                    Request::route()->getName() == 'report.income.summary' ||
                                    Request::route()->getName() == 'report.expense.summary' ||
                                    Request::route()->getName() == 'report.income.vs.expense.summary' ||
                                    Request::route()->getName() == 'report.tax.summary' ||
                                    Request::route()->getName() == 'hrm.dashboard' ||
                                    Request::segment(1) == 'reports-leave' ||
                                    Request::segment(1) == 'reports-monthly-attendance' ||
                                    Request::segment(1) == 'crm-dashboard' ||
                                    Request::route()->getName() == 'project.dashboard'
                                        ? 'active dash-trigger'
                                        : '' }}">
                        <a href="#!" data-toggle="collapse" aria-expanded="false"
                            class="dropdown-toggle menu-item">
                            <span class="dash-micon">
                                <i class="ti ti-home"></i>
                            </span>
                            <span class="dash-mtext">{{ __('Dashboard') }}</span>
                        </a>
                        <ul class="dash-submenu">
                            @if (
                                $userPlan->account == 1 &&
                                (
                                    Gate::check('show account dashboard') ||
                                    // Allow Accounting menu for users who at least have GRN permissions
                                    Gate::check('view grn') ||
                                    Gate::check('create grn') ||
                                    Gate::check('edit grn') ||
                                    Gate::check('delete grn')
                                )
                            )
                                <li
                                    class="dash-item  {{ Request::segment(1) == 'account-dashboard' ||Request::segment(1) == 'stock-overview' ||Request::segment(1) == 'sell-overview' ||Request::segment(1) == 'reports-monthly-cashflow' ||Request::segment(1) == 'reports-quarterly-cashflow' ||Request::route()->getName() == 'report.account.statement' ||Request::route()->getName() == 'report.customer.statement' ||Request::route()->getName() == 'report.vendor.statement' ||Request::route()->getName() == 'report.employee.statement' ||Request::route()->getName() == 'report.invoice.summary' ||Request::route()->getName() == 'report.sales' ||Request::route()->getName() == 'subproduct.sell_report' ||Request::route()->getName() == 'subproduct.stock_movement_report' ||Request::route()->getName() == 'report.receivables' ||Request::route()->getName() == 'report.payables' ||Request::route()->getName() == 'report.bill.summary' ||Request::route()->getName() == 'report.product.stock.report' ||Request::route()->getName() == 'report.item.master' ||Request::route()->getName() == 'transaction.index' ||Request::route()->getName() == 'report.income.summary' ||Request::route()->getName() == 'report.expense.summary' ||Request::route()->getName() == 'report.income.vs.expense.summary' ||Request::route()->getName() == 'report.tax.summary'? ' active dash-trigger': '' }}">
                                    <a data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"
                                        href="#">{{ __('Accounting ') }} </a>
                                    <ul class="dash-submenu">
                                        @can('show account dashboard')
                                            <li
                                                class="dash-item {{ Request::segment(1) == 'account-dashboard' ? ' active' : '' }}">
                                                <a class="dash-link"
                                                    href="{{ route('dashboard') }}">{{ __(' Overview') }}</a>
                                            </li>
                                            <li
                                                class="dash-item {{ Request::segment(1) == 'stock-overview' ? ' active' : '' }}">
                                                <a class="dash-link"
                                                    href="{{ route('stock.overview') }}">{{ __(' Stock Overview') }}</a>
                                            </li>
                                            <li
                                                class="dash-item {{ Request::segment(1) == 'sell-overview' ? ' active' : '' }}">
                                                <a class="dash-link"
                                                    href="{{ route('sell.overview') }}">{{ __(' Sell Overview') }}</a>
                                            </li>
                                        @endcan
                                        @if (Gate::check('income report') ||
                                                Gate::check('expense report') ||
                                                Gate::check('income vs expense report') ||
                                                Gate::check('tax report') ||
                                                Gate::check('loss & profit report') ||
                                                Gate::check('invoice report') ||
                                                Gate::check('bill report') ||
                                                Gate::check('stock report') ||
                                                Gate::check('invoice report') ||
                                                Gate::check('manage transaction') ||
                                                Gate::check('statement customer') ||
                                                Gate::check('statement vendor') ||
                                                Request::route()->getName() == 'report.account.statement' ||
                                                Request::route()->getName() == 'report.customer.statement' ||
                                                Request::route()->getName() == 'report.vendor.statement' ||
                                                Request::route()->getName() == 'report.employee.statement' ||
                                                Request::route()->getName() == 'report.invoice.summary' ||
                                                Request::route()->getName() == 'report.sales' ||
                                                Request::route()->getName() == 'subproduct.stock_movement_report' ||
                                                Request::route()->getName() == 'report.receivables' ||
                                                Request::route()->getName() == 'report.payables' ||
                                                Request::route()->getName() == 'report.bill.summary' ||
                                                Request::route()->getName() == 'report.product.stock.report' ||
                                                Request::route()->getName() == 'transaction.index' ||
                                                Request::route()->getName() == 'report.income.summary' ||
                                                Request::route()->getName() == 'report.expense.summary' ||
                                                Request::route()->getName() == 'report.income.vs.expense.summary' ||
                                                Request::route()->getName() == 'report.tax.summary' ||
                                                Gate::check('statement report'))
                                            <li
                                                class="dash-item  {{ Request::segment(1) == 'reports-monthly-cashflow' || Request::segment(1) == 'reports-quarterly-cashflow' || Request::route()->getName() == 'report.account.statement' || Request::route()->getName() == 'report.customer.statement' || Request::route()->getName() == 'report.vendor.statement' || Request::route()->getName() == 'report.employee.statement' || Request::route()->getName() == 'report.invoice.summary' || Request::route()->getName() == 'report.sales' || Request::route()->getName() == 'subproduct.sell_report' || Request::route()->getName() == 'subproduct.stock_movement_report' || Request::route()->getName() == 'report.receivables' || Request::route()->getName() == 'report.payables' || Request::route()->getName() == 'report.bill.summary' || Request::route()->getName() == 'report.product.stock.report' || Request::route()->getName() == 'transaction.index' || Request::route()->getName() == 'report.income.summary' || Request::route()->getName() == 'report.expense.summary' || Request::route()->getName() == 'report.income.vs.expense.summary' || Request::route()->getName() == 'report.tax.summary'? 'active dash-trigger ': '' }}">
                                                <a data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"
                                                    href="#">{{ __('Reports') }}</a>
                                                <ul class="dash-submenu">
                                                    @can('statement report')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.account.statement' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.account.statement') }}">{{ __('Account Statement') }}</a>
                                                        </li>
                                                    @endcan
                                                    @can('statement customer')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.customer.statement' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.customer.statement') }}">{{ __('Customer Statement') }}</a>
                                                        </li>
                                                    @endcan
                                                    @can('statement vendor')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.vendor.statement' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.vendor.statement') }}">{{ __('Vendor Statement') }}</a>
                                                        </li>
                                                    @endcan
                                                    @can('statement vendor')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.employee.statement' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.employee.statement') }}">{{ __('Employee Statement') }}</a>
                                                        </li>
                                                    @endcan
                                                    @can('invoice report')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.invoice.summary' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.invoice.summary') }}">{{ __('Invoice Summary') }}</a>
                                                        </li>
                                                    @endcan
                                                    <li
                                                        class="dash-item {{ Request::route()->getName() == 'report.sales' ? ' active' : '' }}">
                                                        <a class="dash-link"
                                                            href="{{ route('report.sales') }}">{{ __('Sales
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        Report') }}</a>
                                                    </li>
                                                    @can('manage product & service')
                                                    <li
                                                        class="dash-item {{ Request::route()->getName() == 'subproduct.sell_report' ? ' active' : '' }}">
                                                        <a class="dash-link"
                                                            href="{{ route('subproduct.sell_report') }}">{{ __('Sell Report') }}</a>
                                                    </li>
                                                    <li
                                                        class="dash-item {{ Request::route()->getName() == 'subproduct.stock_movement_report' ? ' active' : '' }}">
                                                        <a class="dash-link"
                                                            href="{{ route('subproduct.stock_movement_report') }}">{{ __('Stock Movement Report') }}</a>
                                                    </li>
                                                    @endcan
                                                    <li
                                                        class="dash-item {{ Request::route()->getName() == 'report.receivables' ? ' active' : '' }}">
                                                        <a class="dash-link"
                                                            href="{{ route('report.receivables') }}">{{ __('Receivables') }}</a>
                                                    </li>
                                                    <li
                                                        class="dash-item {{ Request::route()->getName() == 'report.payables' ? ' active' : '' }}">
                                                        <a class="dash-link"
                                                            href="{{ route('report.payables') }}">{{ __('Payables') }}</a>
                                                    </li>
                                                    @can('bill report')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.bill.summary' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.bill.summary') }}">{{ __('Bill Summary') }}</a>
                                                        </li>
                                                    @endcan
                                                    @can('stock report')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.product.stock.report' ? ' active' : '' }}">
                                                            <a href="{{ route('report.product.stock.report') }}"
                                                                class="dash-link">{{ __('Product Stock') }}</a>
                                                        </li>
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.item.master' ? ' active' : '' }}">
                                                            <a href="{{ route('report.item.master') }}"
                                                                class="dash-link">{{ __('Item Master') }}</a>
                                                        </li>
                                                        
                                                    @endcan

                                                    @can('loss & profit report')
                                                        <li
                                                            class="dash-item {{ request()->is('reports-monthly-cashflow') || request()->is('reports-quarterly-cashflow') ? 'active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.monthly.cashflow') }}">{{ __('Cash Flow') }}</a>
                                                        </li>
                                                    @endcan
                                                    @can('manage transaction')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'transaction.index' || Request::route()->getName() == 'transfer.create' || Request::route()->getName() == 'transaction.edit' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('transaction.index') }}">{{ __('Transaction') }}</a>
                                                        </li>
                                                    @endcan
                                                    @can('income report')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.income.summary' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.income.summary') }}">{{ __('Income Summary') }}</a>
                                                        </li>
                                                    @endcan
                                                    @can('expense report')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.expense.summary' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.expense.summary') }}">{{ __('Expense Summary') }}</a>
                                                        </li>
                                                    @endcan
                                                    @can('income vs expense report')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.income.vs.expense.summary' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.income.vs.expense.summary') }}">{{ __('Income
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    VS Expense') }}</a>
                                                        </li>
                                                    @endcan
                                                    @can('tax report')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.tax.summary' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.tax.summary') }}">{{ __('Tax
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    Summary') }}</a>
                                                        </li>
                                                    @endcan
                                                </ul>
                                            </li>
                                        @endif
                                    </ul>
                                </li>
                            @endif

                            @unless ($motorHideSidebarExtras)
                            @if ($userPlan->hrm == 1)
                                @can('show hrm dashboard')
                                    <li
                                        class="dash-item  {{ Request::route()->getName() == 'hrm.dashboard' || Request::segment(1) == 'hrm.dashboard' || Request::segment(1) == 'reports-payroll' || Request::segment(1) == 'reports-leave' || Request::segment(1) == 'reports-monthly-attendance' ? ' active dash-trigger' : '' }}">
                                        <a data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"
                                            href="#">{{ __('HRM ') }}</a>
                                        <ul class="dash-submenu">
                                            <li
                                                class="dash-item {{ Request::route()->getName() == 'hrm.dashboard' ? ' active' : '' }}">
                                                <a class="dash-link"
                                                    href="{{ route('hrm.dashboard') }}">{{ __(' Overview') }}</a>
                                            </li>
                                            @can('manage report')
                                                <li class="dash-item
                                                                        {{ Request::segment(1) == 'reports-monthly-attendance' ||
                                                                        Request::segment(1) == 'reports-leave' ||
                                                                        Request::segment(1) == 'reports-payroll'
                                                                            ? 'active dash-trigger'
                                                                            : '' }}"
                                                    href="#hr-report" data-toggle="collapse" role="button"
                                                    aria-expanded="{{ Request::segment(1) == 'reports-monthly-attendance' || Request::segment(1) == 'reports-leave' || Request::segment(1) == 'reports-payroll' ? 'true' : 'false' }}">
                                                    <a data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"
                                                        href="#">{{ __('Reports') }}</a>
                                                    <ul class="dash-submenu">
                                                        <li
                                                            class="dash-item {{ request()->is('reports-payroll') ? 'active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.payroll') }}">{{ __('Payroll') }}</a>
                                                        </li>
                                                        <li
                                                            class="dash-item {{ request()->is('reports-leave') ? 'active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.leave') }}">{{ __('Leave') }}</a>
                                                        </li>
                                                        <li
                                                            class="dash-item {{ request()->is('reports-monthly-attendance') ? 'active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.monthly.attendance') }}">{{ __('Monthly Attendance') }}</a>
                                                        </li>
                                                    </ul>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endcan
                            @endif

                            @if ($userPlan->crm == 1)
                                @can('show crm dashboard')
                                    <li
                                        class="dash-item  {{ Request::segment(1) == 'crm-dashboard' || Request::segment(1) == 'reports-lead' || Request::segment(1) == 'reports-deal' ? ' active dash-trigger' : '' }}">
                                        <a data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"
                                            href="#">{{ __('CRM') }}</a>
                                        <ul class="dash-submenu">
                                            <li
                                                class="dash-item {{ \Request::route()->getName() == 'crm.dashboard' ? ' active' : '' }}">
                                                <a class="dash-link"
                                                    href="{{ route('crm.dashboard') }}">{{ __(' Overview') }}</a>
                                            </li>
                                            @if (auth()->user()->type === 'company')
                                                <li class="dash-item  {{ Request::segment(1) == 'reports-lead' || Request::segment(1) == 'reports-deal' ? 'active dash-trigger' : '' }}"
                                                    href="#crm-report" data-toggle="collapse" role="button"
                                                    aria-expanded="{{ Request::segment(1) == 'reports-lead' || Request::segment(1) == 'reports-deal' ? 'true' : 'false' }}">
                                                    <a data-toggle="collapse" aria-expanded="false"
                                                        class="dropdown-toggle" href="#">{{ __('Reports') }}</a>
                                                    <ul class="dash-submenu">
                                                        <li
                                                            class="dash-item {{ request()->is('reports-lead') ? 'active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.lead') }}">{{ __('Lead') }}</a>
                                                        </li>
                                                        <li
                                                            class="dash-item {{ request()->is('reports-deal') ? 'active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.deal') }}">{{ __('Deal') }}</a>
                                                        </li>
                                                    </ul>
                                                </li>
                                            @endif
                                        </ul>
                                    </li>
                                @endcan
                            @endif

                            @if ($userPlan->project == 1)
                                @can('show project dashboard')
                                    <li
                                        class="dash-item {{ Request::route()->getName() == 'project.dashboard' ? ' active' : '' }}">
                                        <a class="dash-link"
                                            href="{{ route('project.dashboard') }}">{{ __('Project ') }}</a>
                                    </li>
                                @endcan
                            @endif
                            @endunless

                            {{-- POS dashboard submenu hidden (see POS System section) --}}

                        </ul>
                    </li>
                @endif
                <!--------------------- End Dashboard ----------------------------------->


                <!--------------------- Start HRM ----------------------------------->
                @unless ($motorHideSidebarExtras)
                @if (!empty($userPlan) && $userPlan->hrm == 1)
                    @if (Gate::check('manage employee') || Gate::check('manage setsalary') || Gate::check('manage daily task log') || Auth::user()->type == 'Employee' ||  Auth::user()->type == 'Sales'|| Gate::check('manage task master'))
                        <li
                            class="dash-item dash-hasmenu {{ Request::segment(1) == 'holiday-calender' ||
                            Request::segment(1) == 'leavetype' ||
                            Request::segment(1) == 'leave' ||
                            Request::segment(1) == 'attendanceemployee' ||
                            Request::segment(1) == 'document-upload' ||
                            Request::segment(1) == 'document' ||
                            Request::segment(1) == 'performanceType' ||
                            Request::segment(1) == 'branch' ||
                            Request::segment(1) == 'department' ||
                            Request::segment(1) == 'designation' ||
                            Request::segment(1) == 'employee' ||
                            Request::segment(1) == 'leave_requests' ||
                            Request::segment(1) == 'holidays' ||
                            Request::segment(1) == 'policies' ||
                            Request::segment(1) == 'leave_calender' ||
                            Request::segment(1) == 'award' ||
                            Request::segment(1) == 'transfer' ||
                            Request::segment(1) == 'resignation' ||
                            Request::segment(1) == 'training' ||
                            Request::segment(1) == 'travel' ||
                            Request::segment(1) == 'promotion' ||
                            Request::segment(1) == 'complaint' ||
                            Request::segment(1) == 'warning' ||
                            Request::segment(1) == 'termination' ||
                            Request::segment(1) == 'announcement' ||
                            Request::segment(1) == 'job' ||
                            Request::segment(1) == 'job-application' ||
                            Request::segment(1) == 'candidates-job-applications' ||
                            Request::segment(1) == 'job-onboard' ||
                            Request::segment(1) == 'custom-question' ||
                            Request::segment(1) == 'interview-schedule' ||
                            Request::segment(1) == 'career' ||
                            Request::segment(1) == 'holiday' ||
                            Request::segment(1) == 'setsalary' ||
                            Request::segment(1) == 'payslip' ||
                            Request::segment(1) == 'paysliptype' ||
                            Request::segment(1) == 'company-policy' ||
                            Request::segment(1) == 'job-stage' ||
                            Request::segment(1) == 'job-category' ||
                            Request::segment(1) == 'terminationtype' ||
                            Request::segment(1) == 'awardtype' ||
                            Request::segment(1) == 'trainingtype' ||
                            Request::segment(1) == 'goaltype' ||
                            Request::segment(1) == 'paysliptype' ||
                            Request::segment(1) == 'allowanceoption' ||
                            Request::segment(1) == 'competencies' ||
                            Request::segment(1) == 'loanoption' ||
                            Request::segment(1) == 'deductionoption' ||
                            Request::segment(1) == 'indicator' ||
                            Request::segment(1) == 'appraisal' ||
                            Request::segment(1) == 'goaltracking' ||
                            Request::segment(1) == 'trainer' ||
                            Request::segment(1) == 'event' ||
                            Request::segment(1) == 'meeting' ||
                            Request::segment(1) == 'account-assets' ||
                            Request::segment(1) == 'earlyleave' ||
                            Request::segment(1) == 'employeepayment' ||
                            Request::segment(1) == 'task-manager' ||
                            Request::segment(1) == 'task-master' ||
                            Request::segment(1) == 'daily-tasks'
                                ? 'active dash-trigger'
                                : '' }}">
                            <a href="#!" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                                <span class="dash-micon">
                                    <i class="ti ti-user"></i>
                                </span>
                                <span class="dash-mtext">
                                    {{ __('HRM System') }}
                                </span>

                            </a>
                            <ul class="dash-submenu">
                                <li
                                    class="dash-item  {{ Request::segment(1) == 'employee' ? 'active dash-trigger' : '' }}   ">
                                    @if (\Auth::user()->type == 'Employee')
                                        @php
                                            $employee = App\Models\Employee::where(
                                                'user_id',
                                                \Auth::user()->id,
                                            )->first();
                                        @endphp
                                        <a class="dash-link"
                                            href="{{ route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($employee->id)) }}">{{ __('Employee') }}</a>
                                    @else
                                        @can('manage employee')
                                            <a href="{{ route('employee.index') }}" class="dash-link">
                                                {{ __('Employee Setup') }}
                                            </a>
                                        @endcan
                                    @endif
                                </li>
                                <li
                                    class="dash-item  {{ Request::segment(1) == 'employeepayment' ? 'active dash-trigger' : '' }}   ">
                                    @if (\Auth::user()->type == 'Employee')
                                        @php
                                            $employee = App\Models\Employee::where(
                                                'user_id',
                                                \Auth::user()->id,
                                            )->first();
                                        @endphp
                                        <a class="dash-link"
                                            href="{{ route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($employee->id)) }}">{{ __('Employee Payment') }}</a>
                                    @else
                                        @can('manage employee')
                                            <a href="{{ route('employeepayment.index') }}" class="dash-link">
                                                {{ __('Employee Payment') }}
                                            </a>
                                        @endcan
                                    @endif
                                </li>
                                @if (Gate::check('manage task master'))
                                <li
                                    class="dash-item {{ Request::segment(1) == 'task-manager' ? 'active dash-trigger' : '' }}">
                                    <a href="{{ route('task.manager.index') }}" class="dash-link">
                                        {{ __('Task Manager') }}
                                    </a>
                                </li>
                                @endif
                                @can('manage task master')
                                <li
                                    class="dash-item {{ Request::segment(1) == 'task-master' ? 'active dash-trigger' : '' }}">
                                    <a href="{{ route('task-master.index') }}" class="dash-link">
                                        {{ __('Task Master') }}
                                    </a>
                                </li>
                                @endcan
                                @if (Auth::user()->type == 'Employee' ||  Auth::user()->type == 'Sales' || Gate::check('manage employee') || Gate::check('manage daily task log'))
                                <li
                                    class="dash-item {{ Request::segment(1) == 'daily-tasks' ? 'active dash-trigger' : '' }}">
                                    <a href="{{ route('daily-tasks.index') }}" class="dash-link">
                                        {{ __('Task') }}
                                    </a>
                                </li>
                                @endif

                                @if (Gate::check('manage set salary') || Gate::check('manage pay slip'))
                                    <li
                                        class="dash-item   {{ Request::segment(1) == 'setsalary' || Request::segment(1) == 'payslip' ? 'active dash-trigger' : '' }}">
                                        <a data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"
                                            href="#">{{ __('Payroll Setup') }}</a>
                                        <ul class="dash-submenu">
                                            @can('manage set salary')
                                                <li class="dash-item {{ request()->is('setsalary*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('setsalary.index') }}">{{ __('Set salary') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage pay slip')
                                                <li class="dash-item {{ request()->is('payslip*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('payslip.index') }}">{{ __('Payslip') }}</a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endif

                                @if (Gate::check('manage leave') || Gate::check('manage attendance'))
                                    <li
                                        class="dash-item   {{ Request::segment(1) == 'leave' || Request::segment(1) == 'attendanceemployee' || Request::segment(1) == 'earlyleave' ? 'active dash-trigger' : '' }}">
                                        <a data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"
                                            href="#">{{ __('Leave Management Setup') }}</a>
                                        <ul class="dash-submenu">
                                            @can('manage leave')
                                                <li
                                                    class="dash-item {{ Request::route()->getName() == 'leave.index' ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('leave.index') }}">{{ __('Manage Leave') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage leave')
                                                <li
                                                    class="dash-item {{ Request::route()->getName() == 'earlyleave.index' ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('earlyleave.index') }}">{{ __('Manage Early
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    Leave') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage attendance')
                                                <li class="dash-item  {{ Request::segment(1) == 'attendanceemployee' ? 'active dash-trigger' : '' }}"
                                                    href="#navbar-attendance" data-toggle="collapse" role="button"
                                                    aria-expanded="{{ Request::segment(1) == 'attendanceemployee' ? 'true' : 'false' }}">
                                                    <a data-toggle="collapse" aria-expanded="false"
                                                        class="dropdown-toggle"
                                                        href="#">{{ __('Attendance') }}</a>
                                                    <ul class="dash-submenu">
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'attendanceemployee.index' ? 'active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('attendanceemployee.index') }}">{{ __('Mark Attendance') }}</a>
                                                        </li>
                                                        @can('create attendance')
                                                            <li
                                                                class="dash-item {{ Request::route()->getName() == 'attendanceemployee.bulkattendance' ? 'active' : '' }}">
                                                                <a class="dash-link"
                                                                    href="{{ route('attendanceemployee.bulkattendance') }}">{{ __('Bulk
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                Attendance') }}</a>
                                                            </li>
                                                        @endcan
                                                    </ul>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endif

                                @if (Gate::check('manage indicator') || Gate::check('manage appraisal') || Gate::check('manage goal tracking'))
                                    <li class="dash-item {{ Request::segment(1) == 'indicator' || Request::segment(1) == 'appraisal' || Request::segment(1) == 'goaltracking' ? 'active dash-trigger' : '' }}"
                                        href="#navbar-performance" data-toggle="collapse" role="button"
                                        aria-expanded="{{ Request::segment(1) == 'indicator' || Request::segment(1) == 'appraisal' || Request::segment(1) == 'goaltracking' ? 'true' : 'false' }}">
                                        <a data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"
                                            href="#">{{ __('Performance Setup') }}</a>
                                        <ul
                                            class="dash-submenu {{ Request::segment(1) == 'indicator' || Request::segment(1) == 'appraisal' || Request::segment(1) == 'goaltracking' ? 'show' : 'collapse' }}">
                                            @can('manage indicator')
                                                <li class="dash-item {{ request()->is('indicator*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('indicator.index') }}">{{ __('Indicator') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage appraisal')
                                                <li class="dash-item {{ request()->is('appraisal*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('appraisal.index') }}">{{ __('Appraisal') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage goal tracking')
                                                <li
                                                    class="dash-item  {{ request()->is('goaltracking*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('goaltracking.index') }}">{{ __('Goal
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    Tracking') }}</a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endif

                                @if (Gate::check('manage training') ||
                                        Gate::check('manage trainer') ||
                                        Gate::check('show
                                                                                                                                                                                                                                                                                                                                                                                            training'))
                                    <li class="dash-item  {{ Request::segment(1) == 'trainer' || Request::segment(1) == 'training' ? 'active dash-trigger' : '' }}"
                                        href="#navbar-training" data-toggle="collapse" role="button"
                                        aria-expanded="{{ Request::segment(1) == 'trainer' || Request::segment(1) == 'training' ? 'true' : 'false' }}">
                                        <a data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"
                                            href="#">{{ __('Training Setup') }}</a>
                                        <ul class="dash-submenu">
                                            @can('manage training')
                                                <li class="dash-item {{ request()->is('training*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('training.index') }}">{{ __('Training List') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage trainer')
                                                <li class="dash-item {{ request()->is('trainer*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('trainer.index') }}">{{ __('Trainer') }}</a>
                                                </li>
                                            @endcan

                                        </ul>
                                    </li>
                                @endif

                                @if (Gate::check('manage job') ||
                                        Gate::check('create job') ||
                                        Gate::check('manage job application') ||
                                        Gate::check('manage custom question') ||
                                        Gate::check('show interview schedule') ||
                                        Gate::check('show career'))
                                    <li
                                        class="dash-item  {{ Request::segment(1) == 'job' || Request::segment(1) == 'job-application' || Request::segment(1) == 'candidates-job-applications' || Request::segment(1) == 'job-onboard' || Request::segment(1) == 'custom-question' || Request::segment(1) == 'interview-schedule' || Request::segment(1) == 'career' ? 'active dash-trigger' : '' }}    ">
                                        <a data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"
                                            href="#">{{ __('Recruitment Setup') }}</a>
                                        <ul class="dash-submenu">
                                            @can('manage job')
                                                <li
                                                    class="dash-item {{ Request::route()->getName() == 'job.index' || Request::route()->getName() == 'job.create' || Request::route()->getName() == 'job.edit' || Request::route()->getName() == 'job.show' ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('job.index') }}">{{ __('Jobs') }}</a>
                                                </li>
                                            @endcan
                                            @can('create job')
                                                <li
                                                    class="dash-item {{ Request::route()->getName() == 'job.create' ? 'active' : '' }} ">
                                                    <a class="dash-link"
                                                        href="{{ route('job.create') }}">{{ __('Job Create') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage job application')
                                                <li
                                                    class="dash-item {{ request()->is('job-application*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('job-application.index') }}">{{ __('Job
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    Application') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage job application')
                                                <li
                                                    class="dash-item {{ request()->is('candidates-job-applications') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('job.application.candidate') }}">{{ __('Job
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    Candidate') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage job application')
                                                <li
                                                    class="dash-item {{ request()->is('job-onboard*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('job.on.board') }}">{{ __('Job On-boarding') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage custom question')
                                                <li
                                                    class="dash-item  {{ request()->is('custom-question*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('custom-question.index') }}">{{ __('Custom
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    Question') }}</a>
                                                </li>
                                            @endcan
                                            @can('show interview schedule')
                                                <li
                                                    class="dash-item {{ request()->is('interview-schedule*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('interview-schedule.index') }}">{{ __('Interview Schedule') }}</a>
                                                </li>
                                            @endcan
                                            @can('show career')
                                                <li class="dash-item {{ request()->is('career*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('career', [\Auth::user()->creatorId(), $lang]) }}">{{ __('Career') }}</a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endif

                                @if (Gate::check('manage award') ||
                                        Gate::check('manage transfer') ||
                                        Gate::check('manage resignation') ||
                                        Gate::check('manage travel') ||
                                        Gate::check('manage promotion') ||
                                        Gate::check('manage complaint') ||
                                        Gate::check('manage warning') ||
                                        Gate::check('manage termination') ||
                                        Gate::check('manage announcement') ||
                                        Gate::check('manage holiday'))
                                    <li
                                        class="dash-item  {{ Request::segment(1) == 'holiday-calender' || Request::segment(1) == 'holiday' || Request::segment(1) == 'policies' || Request::segment(1) == 'award' || Request::segment(1) == 'transfer' || Request::segment(1) == 'resignation' || Request::segment(1) == 'travel' || Request::segment(1) == 'promotion' || Request::segment(1) == 'complaint' || Request::segment(1) == 'warning' || Request::segment(1) == 'termination' || Request::segment(1) == 'announcement' || Request::segment(1) == 'competencies' ? 'active dash-trigger' : '' }}">
                                        <a data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"
                                            href="#">{{ __('HR Admin Setup') }}</a>
                                        <ul class="dash-submenu">
                                            @can('manage award')
                                                <li class="dash-item {{ request()->is('award*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('award.index') }}">{{ __('Award') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage transfer')
                                                <li class="dash-item  {{ request()->is('transfer*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('transfer.index') }}">{{ __('Transfer') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage resignation')
                                                <li
                                                    class="dash-item {{ request()->is('resignation*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('resignation.index') }}">{{ __('Resignation') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage travel')
                                                <li class="dash-item {{ request()->is('travel*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('travel.index') }}">{{ __('Trip') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage promotion')
                                                <li class="dash-item {{ request()->is('promotion*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('promotion.index') }}">{{ __('Promotion') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage complaint')
                                                <li class="dash-item {{ request()->is('complaint*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('complaint.index') }}">{{ __('Complaints') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage warning')
                                                <li class="dash-item {{ request()->is('warning*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('warning.index') }}">{{ __('Warning') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage termination')
                                                <li
                                                    class="dash-item {{ request()->is('termination*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('termination.index') }}">{{ __('Termination') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage announcement')
                                                <li
                                                    class="dash-item {{ request()->is('announcement*') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('announcement.index') }}">{{ __('Announcement') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage holiday')
                                                <li
                                                    class="dash-item {{ request()->is('holiday*') || request()->is('holiday-calender') ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('holiday.index') }}">{{ __('Holidays') }}</a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endif

                                @can('manage event')
                                    <li class="dash-item {{ request()->is('event*') ? 'active' : '' }}">
                                        <a class="dash-link"
                                            href="{{ route('event.index') }}">{{ __('Event Setup') }}</a>
                                    </li>
                                @endcan
                                @can('manage meeting')
                                    <li class="dash-item {{ request()->is('meeting*') ? 'active' : '' }}">
                                        <a class="dash-link"
                                            href="{{ route('meeting.index') }}">{{ __('Meeting') }}</a>
                                    </li>
                                @endcan
                                @can('manage assets')
                                    <li class="dash-item {{ request()->is('account-assets*') ? 'active' : '' }}">
                                        <a class="dash-link"
                                            href="{{ route('account-assets.index') }}">{{ __('Employees Asset
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        Setup ') }}</a>
                                    </li>
                                @endcan
                                @can('manage document')
                                    <li class="dash-item {{ request()->is('document-upload*') ? 'active' : '' }}">
                                        <a class="dash-link"
                                            href="{{ route('document-upload.index') }}">{{ __('Document Setup') }}</a>
                                    </li>
                                @endcan
                                @can('manage company policy')
                                    <li class="dash-item {{ request()->is('company-policy*') ? 'active' : '' }}">
                                        <a class="dash-link"
                                            href="{{ route('company-policy.index') }}">{{ __('Company policy') }}</a>
                                    </li>
                                @endcan

                                @if (\Auth::user()->type == 'company' || \Auth::user()->type == 'HR')
                                    <li
                                        class="dash-item {{ Request::segment(1) == 'leavetype' ||
                                        Request::segment(1) == 'document' ||
                                        Request::segment(1) == 'performanceType' ||
                                        Request::segment(1) == 'branch' ||
                                        Request::segment(1) == 'department' ||
                                        Request::segment(1) == 'designation' ||
                                        Request::segment(1) == 'job-stage' ||
                                        Request::segment(1) == 'performanceType' ||
                                        Request::segment(1) == 'job-category' ||
                                        Request::segment(1) == 'terminationtype' ||
                                        Request::segment(1) == 'awardtype' ||
                                        Request::segment(1) == 'trainingtype' ||
                                        Request::segment(1) == 'goaltype' ||
                                        Request::segment(1) == 'paysliptype' ||
                                        Request::segment(1) == 'allowanceoption' ||
                                        Request::segment(1) == 'loanoption' ||
                                        Request::segment(1) == 'deductionoption'
                                            ? 'active dash-trigger'
                                            : '' }}">
                                        <a class="dash-link"
                                            href="{{ route('branch.index') }}">{{ __('HRM System Setup') }}</a>
                                    </li>
                                @endcan


                        </ul>
                    </li>
                @endif
            @endif
                @endunless

            <!--------------------- End HRM ----------------------------------->

            <!--------------------- Start Account ----------------------------------->

            @if (!empty($userPlan) && $userPlan->account == 1)
                @if (Gate::check('manage customer') ||
                        Gate::check('manage vender') ||
                        Gate::check('manage customer') ||
                        Gate::check('manage vender') ||
                        Gate::check('manage proposal') ||
                        Gate::check('manage bank account') ||
                        Gate::check('manage bank transfer') ||
                        Gate::check('manage invoice') ||
                        Gate::check('manage revenue') ||
                        Gate::check('manage credit note') ||
                        Gate::check('manage bill') ||
                        Gate::check('manage payment') ||
                        Gate::check('manage refund') ||
                        Gate::check('manage debit note') ||
                        Gate::check('manage chart of account') ||
                        Gate::check('manage journal entry') ||
                        Gate::check('balance sheet report') ||
                        Gate::check('ledger report') ||
                        Gate::check('general ledger report') ||
                        Gate::check('manage manufacturer') ||
                        Gate::check('trial balance report'))
                    <li
                        class="dash-item dash-hasmenu
                                         {{ Request::route()->getName() == 'print-setting' ||
                                         Request::segment(1) == 'customer' ||
                                         Request::segment(1) == 'vender' ||
                                         Request::segment(1) == 'proposal' ||
                                         Request::segment(1) == 'bank-account' ||
                                         Request::segment(1) == 'bank-transfer' ||
                                         Request::segment(1) == 'invoice' ||
                                         Request::segment(1) == 'revenue' ||
                                         Request::segment(1) == 'credit-note' ||
                                         Request::segment(1) == 'taxes' ||
                                         Request::segment(1) == 'product-category' ||
                                         Request::segment(1) == 'product-unit' ||
                                         Request::segment(1) == 'payment-method' ||
                                         Request::segment(1) == 'custom-field' ||
                                         Request::segment(1) == 'chart-of-account-type' ||
                                         Request::segment(1) == 'countries' ||
                                         Request::segment(1) == 'currency' ||
                                         // Fix the transaction condition grouping
                                         (Request::segment(1) == 'transaction' &&
                                             Request::segment(2) != 'ledger' &&
                                             Request::segment(2) != 'Gledger' &&
                                             Request::segment(2) != 'balance-sheet' &&
                                             Request::segment(2) != 'trial-balance' && // Changed || to &&
                                             Request::segment(2) != 'trial-balance-total') ||
                                         Request::segment(1) == 'goal' ||
                                         Request::segment(1) == 'budget' ||
                                         Request::segment(1) == 'chart-of-account' ||
                                         Request::segment(1) == 'journal-entry' ||
                                         Request::segment(2) == 'ledger' ||
                                         Request::segment(2) == 'Gledger' ||
                                         Request::segment(2) == 'balance-sheet' ||
                                         Request::segment(2) == 'trial-balance' ||
                                         Request::segment(2) == 'trial-balance-total' ||
                                         Request::segment(2) == 'profit-loss' ||
                                         Request::segment(1) == 'bill' ||
                                         Request::segment(1) == 'manufacturers' ||
                                         Request::segment(1) == 'expense' ||
                                         Request::segment(1) == 'payment' ||
                                         Request::segment(1) == 'debit-note' ||
                                         Request::segment(1) == 'customerpayment' ||
                                         Request::segment(1) == 'customerrefund' ||
                                         Request::segment(1) == 'refund'
                                             ? ' active dash-trigger'
                                             : '' }}">
                        <a href="#!" data-toggle="collapse" aria-expanded="false"
                            class="dropdown-toggle"><span class="dash-micon"><i
                                    class="ti ti-box"></i></span><span
                                class="dash-mtext">{{ __('Accounting System ') }}
                            </span>
                        </a>
                        <ul class="dash-submenu">

                            @if (Gate::check('manage bank account') || Gate::check('manage bank transfer'))
                                <li
                                    class="dash-item  {{ Request::segment(1) == 'bank-account' || Request::segment(1) == 'bank-transfer' ? 'active dash-trigger' : '' }}">
                                    <a data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"
                                        href="#">{{ __('Banking') }} </a>
                                    <ul class="dash-submenu">
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'bank-account.index' || Request::route()->getName() == 'bank-account.create' || Request::route()->getName() == 'bank-account.edit' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('bank-account.index') }}">{{ __('Account') }}</a>
                                        </li>
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'bank-transfer.index' || Request::route()->getName() == 'bank-transfer.create' || Request::route()->getName() == 'bank-transfer.edit' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('bank-transfer.index') }}">{{ __('Transfer') }}</a>
                                        </li>
                                    </ul>
                                </li>
                            @endif
                            @if (Gate::check('manage customer') ||
                                    Gate::check('manage proposal') ||
                                    Gate::check('manage invoice') ||
                                    Gate::check('create sale order') ||
                                    Gate::check('view advance sale order') ||
                                    Gate::check('create advance sale order') ||
                                    Gate::check('manage customer payment') ||
                                    Gate::check('manage revenue') ||
                                    Gate::check('manage credit note'))
                                <li
                                    class="dash-item  {{ Request::segment(1) == 'customer' || Request::segment(1) == 'proposal' || Request::segment(1) == 'invoice' || Request::segment(1) == 'saleorder' || Request::segment(1) == 'advance-saleorder' || Request::segment(1) == 'revenue' || Request::segment(1) == 'credit-note' || Request::segment(1) == 'customerpayment' || Request::segment(1) == 'customerrefund' || Request::segment(1) == 'sales-return' ? 'active dash-trigger' : '' }}">
                                    <a data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"
                                        href="#">{{ __('Sales') }} </a>
                                    <ul class="dash-submenu">
                                        @if (Gate::check('manage customer'))
                                            <li
                                                class="dash-item {{ Request::segment(1) == 'customer' ? 'active' : '' }}">
                                                <a class="dash-link"
                                                    href="{{ route('customer.index') }}">{{ __('Customer') }}</a>
                                            </li>
                                        @endif
                                        @if (Gate::check('manage proposal'))
                                            <li
                                                class="dash-item {{ Request::segment(1) == 'proposal' ? 'active' : '' }}">
                                                <a class="dash-link"
                                                    href="{{ route('proposal.index') }}">{{ __('Estimate') }}</a>
                                            </li>
                                        @endif
                                        @if (Gate::check('create sale order') || Gate::check('view advance sale order') || Gate::check('create advance sale order'))
                                            <li
                                                class="dash-item {{ Request::segment(1) == 'saleorder' ? 'active' : '' }}">
                                                <a class="dash-link"
                                                    href="{{ route('saleorder.index') }}">{{ __('Sale Orders') }}</a>
                                            </li>
                                            <li
                                                class="dash-item {{ Request::segment(1) == 'advance-saleorder' ? 'active' : '' }}">
                                                <a class="dash-link"
                                                    href="{{ route('advance-saleorder.index') }}">{{ __('Advance Sale Orders') }}</a>
                                            </li>
                                            <li
                                                class="dash-item {{ Request::segment(1) == 'picklist' ? 'active' : '' }}">
                                                <a class="dash-link"
                                                    href="{{ route('picklist.index') }}">{{ __('Pick Lists') }}</a>
                                            </li>
                                            <li
                                                class="dash-item {{ Request::segment(1) == 'packinglist' ? 'active' : '' }}">
                                                <a class="dash-link"
                                                    href="{{ route('packinglist.index') }}">{{ __('Packing Lists') }}</a>
                                            </li>
                                        @endif
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'invoice.index' || Request::route()->getName() == 'invoice.create' || Request::route()->getName() == 'invoice.edit' || Request::route()->getName() == 'invoice.show' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('invoice.index') }}">{{ __('Invoice') }}</a>
                                        </li>
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'sales.return.index' || Request::route()->getName() == 'sales.return.create' || Request::route()->getName() == 'sales.return.show' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('sales.return.index') }}">{{ __('Sales Return') }}</a>
                                        </li>
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'revenue.index' || Request::route()->getName() == 'revenue.create' || Request::route()->getName() == 'revenue.edit' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('revenue.index') }}">{{ __('Revenue') }}</a>
                                        </li>
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'customerpayment.index' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('customerpayment.index') }}">{{ __('Customer
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            Payment') }}</a>
                                        </li>
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'customerrefund.index' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('customerrefund.index') }}">{{ __('Customer
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            Refund') }}</a>
                                        </li>
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'credit.note' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('credit.note') }}">{{ __('Credit Note') }}</a>
                                        </li>
                                    </ul>
                                </li>
                            @endif
                            @if (Gate::check('manage vender') ||
                                    Gate::check('manage bill') ||
                                    Gate::check('manage payment') ||
                                    Gate::check('manage manufacturer') ||
                                    Gate::check('manage debit note'))
                                <li
                                    class="dash-item  {{ Request::segment(1) == 'bill' || Request::segment(1) == 'vender' || Request::segment(1) == 'expense' || Request::segment(1) == 'simple-expense' || Request::segment(1) == 'simple-expense-payments' || Request::segment(1) == 'payment' || Request::segment(1) == 'debit-note' || Request::segment(1) == 'purchase-return' || Request::route()->getName() == 'refund.index' || Request::route()->getName() == 'purchase.return.create' || Request::route()->getName() == 'purchase.return.store' ? 'active dash-trigger' : '' }}">
                                    <a data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"
                                        href="#">{{ __('Purchases') }} </a>
                                    <ul class="dash-submenu">
                                        @if (Gate::check('manage vender'))
                                            <li
                                                class="dash-item {{ Request::segment(1) == 'vender' ? 'active' : '' }}">
                                                <a class="dash-link"
                                                    href="{{ route('vender.index') }}">{{ __('Suppiler') }}</a>
                                            </li>
                                        @endif
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'bill.index' || Request::route()->getName() == 'bill.create' || Request::route()->getName() == 'bill.edit' || Request::route()->getName() == 'bill.show' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('bill.index') }}">{{ __('Bill') }}</a>
                                        </li>
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'purchase.return.index' || Request::route()->getName() == 'purchase.return.create' || Request::route()->getName() == 'purchase.return.store' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('purchase.return.index') }}">{{ __('Purchase Return') }}</a>
                                        </li>
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'car_accessories.index' || Request::route()->getName() == 'car_accessories.create' || Request::route()->getName() == 'car_accessories.edit' || Request::route()->getName() == 'car_accessories.show' || Request::route()->getName() == 'car_accessories.search' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('car_accessories.index') }}">{{ __('Manufacturers') }}</a>
                                        </li>
                                        {{-- <li
                                            class="dash-item {{ Request::route()->getName() == 'expense.index' || Request::route()->getName() == 'expense.create' || Request::route()->getName() == 'expense.edit' || Request::route()->getName() == 'expense.show' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('expense.index') }}">{{ __('Expense') }}</a>
                                        </li> --}}
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'simple-expense.index' || Request::route()->getName() == 'simple-expense.create' || Request::route()->getName() == 'simple-expense.edit' || Request::route()->getName() == 'simple-expense.show' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('simple-expense.index') }}">{{ __('Service Bill') }}</a>
                                        </li>
                                        @if (Gate::check('manage payment') || Gate::check('manage bill'))
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'simple-expense-payments.index' || Request::route()->getName() == 'simple-expense-payments.create' || Request::route()->getName() == 'simple-expense-payments.edit' || Request::route()->getName() == 'simple-expense-payments.show' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('simple-expense-payments.index') }}">{{ __('Service Bill Payments') }}</a>
                                        </li>
                                        @endif
                                        <li
                                            class="dash-item {{ in_array(Request::route()->getName(), ['direct_expenses.index','direct_expenses.edit']) ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('direct_expenses.index') }}">{{ __('Direct Expense') }}</a>
                                        </li>
                                        <li
                                            class="dash-item {{ in_array(Request::route()->getName(), ['direct_expense_payments.index','direct_expense_payments.show','direct_expense_payments.edit','direct_expense_payments.create']) ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('direct_expense_payments.index') }}">{{ __('Direct Expense Payments') }}</a>
                                        </li>
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'payment.index' || Request::route()->getName() == 'payment.create' || Request::route()->getName() == 'payment.edit' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('payment.index') }}">{{ __('Payment') }}</a>
                                        </li>
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'refund.index' || Request::route()->getName() == 'refund.create' || Request::route()->getName() == 'refund.edit' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('refund.index') }}">{{ __('Refund') }}</a>
                                        </li>
                                        <li
                                            class="dash-item  {{ Request::route()->getName() == 'debit.note' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('debit.note') }}">{{ __('Debit Note') }}</a>
                                        </li>
                                    </ul>
                                </li>
                            @endif
                            @if (Gate::check('manage chart of account') ||
                                    Gate::check('manage journal entry') ||
                                    Gate::check('balance sheet report') ||
                                    Gate::check('ledger report') ||
                                    Gate::check('general ledger report') ||
                                    Gate::check('trial balance report'))
                                <li
                                    class="dash-item  {{ Request::segment(1) == 'chart-of-account' ||
                                    Request::route()->getName() == 'chart-of-account.chart_setup' ||
                                    Request::segment(1) == 'journal-entry' ||
                                    Request::segment(1) == 'profit-loss' ||
                                    Request::segment(1) == 'ledger' ||
                                    Request::segment(1) == 'balance-sheet' ||
                                    Request::segment(1) == 'trial-balance' ||
                                    Request::segment(1) == 'trial-balance-total' ||
                                    Request::segment(1) == 'Gledger' ||
                                    Request::route()->getName() == 'report.ledger' ||
                                    Request::route()->getName() == 'report.Gledger' ||
                                    Request::route()->getName() == 'report.balance.sheet' ||
                                    Request::route()->getName() == 'report.company.tax' ||
                                    Request::route()->getName() == 'report.profit.loss' ||
                                    Request::route()->getName() == 'trial.balance'
                                        ? 'active dash-trigger'
                                        : '' }}">
                                    <a data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"
                                        href="#">{{ __('Double Entry') }} </a>
                                    <ul class="dash-submenu">
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'chart-of-account.index' || Request::route()->getName() == 'chart-of-account.show' || Request::route()->getName() == 'chart-of-account.chart_setup' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('chart-of-account.index') }}">{{ __('Chart
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            of Accounts') }}</a>
                                        </li>
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'journal-entry.edit' ||
                                            Request::route()->getName() == 'journal-entry.create' ||
                                            Request::route()->getName() == 'journal-entry.index' ||
                                            Request::route()->getName() == 'journal-entry.show'
                                                ? ' active'
                                                : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('journal-entry.index') }}">{{ __('Journal
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            Account') }}</a>
                                        </li>
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'report.ledger' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('report.ledger', 0) }}">{{ __('Ledger
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            Summary') }}</a>
                                        </li>
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'report.Gledger' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('report.Gledger', 0) }}">{{ __('General
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            Ledger') }}</a>
                                        </li>
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'report.balance.sheet' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('report.balance.sheet') }}">{{ __('Balance
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            Sheet') }}</a>
                                        </li>
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'report.company.tax' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('report.company.tax') }}">{{ __('Company Tax Report') }}</a>
                                        </li>
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'report.profit.loss' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('report.profit.loss') }}">{{ __('Profit &
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            Loss') }}</a>
                                        </li>

                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'trial.balance' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('trial.balance') }}">{{ __('Trial Balance') }}</a>
                                        </li>

                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'trial.balance.total' ? ' active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('trial.balance.total') }}">{{ __('Trial
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            Balance Total') }}</a>
                                        </li>
                                    </ul>
                                </li>
                            @endif
                            @if (\Auth::user()->type == 'company')
                                <li class="dash-item {{ Request::segment(1) == 'budget' ? 'active' : '' }}">
                                    <a class="dash-link"
                                        href="{{ route('budget.index') }}">{{ __('Budget Planner') }}</a>
                                </li>
                            @endif
                            @if (Gate::check('manage goal'))
                                <li class="dash-item {{ Request::segment(1) == 'goal' ? 'active' : '' }}">
                                    <a class="dash-link"
                                        href="{{ route('goal.index') }}">{{ __('Financial Goal') }}</a>
                                </li>
                            @endif
                            @if (Gate::check('manage constant tax') ||
                                    Gate::check('manage constant category') ||
                                    Gate::check('manage constant unit') ||
                                    Gate::check('manage constant payment method') ||
                                    Gate::check('manage constant brand') ||
                                    Gate::check('manage constant sub-brand') ||
                                    Gate::check('manage constant custom field') ||
                                    (Gate::check('create constant countries') ||
                                        Gate::check('edit constant countries') ||
                                        Gate::check('delete constant countries')) ||
                                    Gate::check('show account setup'))
                                <li
                                    class="dash-item {{ Request::segment(1) == 'taxes' || Request::segment(1) == 'product-category' || Request::segment(1) == 'product-unit' || Request::segment(1) == 'payment-method' || Request::segment(1) == 'custom-field' || Request::segment(1) == 'chart-of-account-type' || Request::segment(1) == 'warehouse' || Request::segment(1) == 'countries' || Request::segment(1) == 'currency' ? 'active dash-trigger' : '' }}">
                                    <a class="dash-link"
                                        href="{{ route('taxes.index') }}">{{ __('Accounting Setup') }}</a>
                                </li>
                            @endif

                            @if (Gate::check('manage print settings'))
                                <li
                                    class="dash-item {{ Request::route()->getName() == 'print-setting' ? ' active' : '' }}">
                                    <a class="dash-link"
                                        href="{{ route('print.setting') }}">{{ __('Print Settings') }}</a>
                                </li>
                            @endif

                        </ul>
                    </li>
                @endif
            @endif

            <!--------------------- End Account ----------------------------------->

            <!--------------------- Start CRM ----------------------------------->
            @unless ($motorHideSidebarExtras)
            @if (!empty($userPlan) && $userPlan->crm == 1)
                @if (Gate::check('manage lead') ||
                        Gate::check('manage crm admin') ||
                        Gate::check('manage deal') ||
                        Gate::check('manage form builder') ||
                        Gate::check('manage contract'))
                    <li
                        class="dash-item dash-hasmenu {{ Request::segment(1) == 'stages' || Request::segment(1) == 'labels' || Request::segment(1) == 'sources' || Request::segment(1) == 'lead_stages' || Request::segment(1) == 'pipelines' || Request::segment(1) == 'lead_roles' || Request::segment(1) == 'campaigns' || Request::segment(1) == 'deals' || Request::segment(1) == 'leads.list' || Request::segment(1) == 'form_builder' || Request::segment(1) == 'form_response' || Request::segment(1) == 'contract' ? ' active dash-trigger' : '' }}">
                        <a href="#!" data-toggle="collapse" aria-expanded="false"
                            class="dropdown-toggle"><span class="dash-micon"><i
                                    class="ti ti-layers-difference"></i></span><span
                                class="dash-mtext">{{ __('CRM System') }}</span></a>
                        <ul
                            class="dash-submenu {{ Request::segment(1) == 'stages' || Request::segment(1) == 'labels' || Request::segment(1) == 'lead_roles' || Request::segment(1) == 'campaigns' || Request::segment(1) == 'sources' || Request::segment(1) == 'lead_stages' || Request::segment(1) == 'leads.list' || Request::segment(1) == 'form_builder' || Request::segment(1) == 'form_response' || Request::segment(1) == 'deals' || Request::segment(1) == 'pipelines' ? 'show' : '' }}">
                            @if (Gate::check('manage lead') || Gate::check('manage crm admin'))
                                <li
                                    class="dash-item {{ Request::route()->getName() == 'leads.list' || Request::route()->getName() == 'leads.index' || Request::route()->getName() == 'leads.show' ? ' active' : '' }}">
                                    <a class="dash-link" href="{{ route('leads.list') }}">{{ __('Leads') }}</a>
                                </li>
                            @endif
                            @if (Gate::check('manage deal') || Gate::check('manage crm admin'))
                                <li
                                    class="dash-item {{ Request::route()->getName() == 'deals.list' || Request::route()->getName() == 'deals.index' || Request::route()->getName() == 'deals.show' ? ' active' : '' }}">
                                    <a class="dash-link" href="{{ route('deals.list') }}">{{ __('Deals') }}</a>
                                </li>
                            @endif
                            @can('manage form builder')
                                <li
                                    class="dash-item {{ Request::segment(1) == 'form_builder' || Request::segment(1) == 'form_response' ? 'active open' : '' }}">
                                    <a class="dash-link"
                                        href="{{ route('form_builder.index') }}">{{ __('Form Builder') }}</a>
                                </li>
                            @endcan
                            @can('manage contract')
                                <li
                                    class="dash-item  {{ Request::route()->getName() == 'contract.index' || Request::route()->getName() == 'contract.show' ? 'active' : '' }}">
                                    <a class="dash-link"
                                        href="{{ route('contract.index') }}">{{ __('Contract') }}</a>
                                </li>
                            
                                <li class="dash-item {{ Request::route()->getName() == 'notifications.index_web' ? 'active' : '' }}">
                                    <a class="dash-link" href="{{ route('notifications.index_web') }}">
                                        {{ __('Notifications') }}
                                    </a>
                                </li>                                
                                <li class="dash-item {{ Request::route()->getName() == 'deal-reminders.index' ? 'active' : '' }}">
                                    <a class="dash-link" href="{{ route('deal-reminders.index') }}">
                                        {{ __('Reminders') }}
                                    </a>
                                </li>
                    @endif
                    @if (Gate::check('manage lead stage') ||
                            Gate::check('manage crm admin') ||
                            Gate::check('manage pipeline') ||
                            Gate::check('manage source') ||
                            Gate::check('manage label') ||
                            Gate::check('manage stage'))
                        <li
                            class="dash-item  {{ Request::segment(1) == 'stages' || Request::segment(1) == 'labels' || Request::segment(1) == 'sources' || Request::segment(1) == 'lead_stages' || Request::segment(1) == 'pipelines' || Request::segment(1) == 'lead_roles' || Request::segment(1) == 'campaigns' || Request::segment(1) == 'product-category' || Request::segment(1) == 'product-unit' || Request::segment(1) == 'payment-method' || Request::segment(1) == 'custom-field' || Request::segment(1) == 'chart-of-account-type' ? 'active dash-trigger' : '' }}">
                            <a class="dash-link"
                                href="{{ route('pipelines.index') }}   ">{{ __('CRM System Setup') }}</a>

                        </li>
                    @endif
                    @if (Gate::check('manage lead role'))
                        <li
                            class="dash-item  {{ Request::segment(1) == 'stages' || Request::segment(1) == 'labels' || Request::segment(1) == 'sources' || Request::segment(1) == 'lead_stages' || Request::segment(1) == 'pipelines' || Request::segment(1) == 'lead_roles' || Request::segment(1) == 'campaigns' || Request::segment(1) == 'product-category' || Request::segment(1) == 'product-unit' || Request::segment(1) == 'payment-method' || Request::segment(1) == 'custom-field' || Request::segment(1) == 'chart-of-account-type' ? 'active dash-trigger' : '' }}">
                            <a class="dash-link"
                                href="{{ route('lead_roles.index') }}   ">{{ __('Lead Role') }}</a>

                        </li>
                    @endif
            </ul>
            </li>
        @endif
        @endif
            @endunless

        <!--------------------- End CRM ----------------------------------->

        <!--------------------- Start Project ----------------------------------->
            @unless ($motorHideSidebarExtras)
        @if (!empty($userPlan) && $userPlan->project == 1)
            @if (Gate::check('manage project'))
                <li
                    class="dash-item dash-hasmenu
                                                {{ Request::segment(1) == 'project' ||
                                                Request::segment(1) == 'bugs-report' ||
                                                Request::segment(1) == 'bugstatus' ||
                                                Request::segment(1) == 'project-task-stages' ||
                                                Request::segment(1) == 'calendar' ||
                                                Request::segment(1) == 'timesheet-list' ||
                                                Request::segment(1) == 'taskboard' ||
                                                Request::segment(1) == 'timesheet-list' ||
                                                Request::segment(1) == 'taskboard' ||
                                                Request::segment(1) == 'project' ||
                                                Request::segment(1) == 'projects' ||
                                                Request::segment(1) == 'project_report'
                                                    ? 'active dash-trigger'
                                                    : '' }}">
                    <a href="#!" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><span
                            class="dash-micon"><i class="ti ti-share"></i></span><span
                            class="dash-mtext">{{ __('Project System') }}</span></a>
                    <ul class="dash-submenu">
                        @can('manage project')
                            <li
                                class="dash-item  {{ Request::segment(1) == 'project' || Request::route()->getName() == 'projects.list' || Request::route()->getName() == 'projects.list' || Request::route()->getName() == 'projects.index' || Request::route()->getName() == 'projects.show' || request()->is('projects/*') ? 'active' : '' }}">
                                <a class="dash-link" href="{{ route('projects.index') }}">{{ __('Projects') }}</a>
                            </li>
                        @endcan
                        @can('manage project task')
                            <li class="dash-item {{ request()->is('taskboard*') ? 'active' : '' }}">
                                <a class="dash-link"
                                    href="{{ route('taskBoard.view', 'list') }}">{{ __('Tasks') }}</a>
                            </li>
                        @endcan
                        @can('manage timesheet')
                            <li class="dash-item {{ request()->is('timesheet-list*') ? 'active' : '' }}">
                                <a class="dash-link" href="{{ route('timesheet.list') }}">{{ __('Timesheet') }}</a>
                            </li>
                        @endcan
                        @can('manage bug report')
                            <li class="dash-item {{ request()->is('bugs-report*') ? 'active' : '' }}">
                                <a class="dash-link" href="{{ route('bugs.view', 'list') }}">{{ __('Bug') }}</a>
                            </li>
                        @endcan
                        @can('manage project task')
                            <li class="dash-item {{ request()->is('calendar*') ? 'active' : '' }}">
                                <a class="dash-link"
                                    href="{{ route('task.calendar', ['all']) }}">{{ __('Task Calendar') }}</a>
                            </li>
                        @endcan
                        @if (\Auth::user()->type != 'super admin')
                            <li class="dash-item  {{ Request::segment(1) == 'time-tracker' ? 'active open' : '' }}">
                                <a class="dash-link" href="{{ route('time.tracker') }}">{{ __('Tracker') }}</a>
                            </li>
                        @endif
                        @if (\Auth::user()->type == 'company' || \Auth::user()->type == 'Employee')
                            <li
                                class="dash-item  {{ Request::route()->getName() == 'project_report.index' || Request::route()->getName() == 'project_report.show' ? 'active' : '' }}">
                                <a class="dash-link"
                                    href="{{ route('project_report.index') }}">{{ __('Project Report') }}</a>
                            </li>
                        @endif

                        @if (Gate::check('manage project task stage') || Gate::check('manage bug status'))
                            <li
                                class="dash-item  {{ Request::segment(1) == 'bugstatus' || Request::segment(1) == 'project-task-stages' ? 'active dash-trigger' : '' }}">
                                <a data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"
                                    href="#">{{ __('Project System Setup') }}</a>
                                <ul class="dash-submenu">
                                    @can('manage project task stage')
                                        <li
                                            class="dash-item  {{ Request::route()->getName() == 'project-task-stages.index' ? 'active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('project-task-stages.index') }}">{{ __('Project Task Stages') }}</a>
                                        </li>
                                    @endcan
                                    @can('manage bug status')
                                        <li
                                            class="dash-item {{ Request::route()->getName() == 'bugstatus.index' ? 'active' : '' }}">
                                            <a class="dash-link"
                                                href="{{ route('bugstatus.index') }}">{{ __('Bug Status') }}</a>
                                        </li>
                                    @endcan
                                </ul>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
        @endif
            @endunless

        <!--------------------- End Project ----------------------------------->



        <!--------------------- Start User Managaement System ----------------------------------->

        @if (
            \Auth::user()->type != 'super admin' &&
                (Gate::check('manage user') || Gate::check('manage role') || Gate::check('manage client')))
            <li
                class="dash-item dash-hasmenu {{ Request::segment(1) == 'users' ||
                Request::segment(1) == 'roles' ||
                Request::segment(1) == 'clients' ||
                Request::segment(1) == 'userlogs'
                    ? ' active dash-trigger'
                    : '' }}">

                <a href="#!" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><span
                        class="dash-micon"><i class="ti ti-users"></i></span><span
                        class="dash-mtext">{{ __('User Management') }}</span></a>
                <ul class="dash-submenu">
                    @can('manage user')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'users.index' || Request::route()->getName() == 'users.create' || Request::route()->getName() == 'users.edit' || Request::route()->getName() == 'user.userlog' ? ' active' : '' }}">
                            <a class="dash-link" href="{{ route('users.index') }}">{{ __('User') }}</a>
                        </li>
                    @endcan
                    @can('manage role')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'roles.index' || Request::route()->getName() == 'roles.create' || Request::route()->getName() == 'roles.edit' ? ' active' : '' }} ">
                            <a class="dash-link" href="{{ route('roles.index') }}">{{ __('Role') }}</a>
                        </li>
                    @endcan
                    @can('manage client')
                        <li
                            class="dash-item {{ Request::route()->getName() == 'clients.index' || Request::segment(1) == 'clients' || Request::route()->getName() == 'clients.edit' ? ' active' : '' }}">
                            <a class="dash-link" href="{{ route('clients.index') }}">{{ __('Client') }}</a>
                        </li>
                    @endcan
                    {{-- @can('manage user') --}}
                    {{-- <li
                                class="dash-item {{ (Request::route()->getName() == 'users.index' || Request::segment(1) == 'users' || Request::route()->getName() == 'users.edit') ? ' active' : '' }}">
                                --}}
                    {{-- <a data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"
                                    href="{{ route('user.userlog') }}">{{__('User Logs')}}</a> --}}
                    {{-- </li> --}}
                    {{-- @endcan --}}
                </ul>
            </li>
        @endif

        <!--------------------- End User Managaement System----------------------------------->


        <!--------------------- Start Products System ----------------------------------->

        @if (Gate::check('manage product & service') || Gate::check('manage product & service'))
            <li
                class="dash-item dash-hasmenu {{ Request::segment(1) == 'productservice' ||
                Request::segment(1) == 'productstock' ||
                Request::segment(1) == 'stock_movements' ||
                Request::segment(1) == 'rent_report' ||
                Request::segment(1) == 'reports_rent_monthly'
                    ? ' active dash-trigger'
                    : '' }}">
                <a href="#!" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <span class="dash-micon"><i class="ti ti-shopping-cart"></i></span><span
                        class="dash-mtext">{{ __('Products System') }}</span>
                </a>
                <ul class="dash-submenu">
                    @if (Gate::check('manage product & service'))
                        <li class="dash-item {{ Request::segment(1) == 'productservice' ? 'active' : '' }}">
                            <a href="{{ route('productservice.index') }}"
                                class="dash-link">{{ __('Product & Services') }}
                            </a>
                        </li>
                    @endif
                    @if (Gate::check('manage product & service'))
                        <li class="dash-item {{ Request::segment(1) == 'productstock' ? 'active' : '' }}">
                            <a href="{{ route('productstock.index') }}"
                                class="dash-link">{{ __('Product Stock') }}
                            </a>
                        </li>
                    @endif
                    <li class="dash-item {{ Request::segment(1) == 'stock_movements' ? 'active' : '' }}">
                        <a href="{{ route('stock_movements.index') }}"
                            class="dash-link">{{ __('Stock
                                                                                                                                                                                                                                                                                                                                                        Movements') }}
                        </a>
                    </li>
                    <li class="dash-item {{ Request::segment(1) == 'rent_report' ? 'active' : '' }}">
                        <a href="{{ route('rent_report') }}" class="dash-link">{{ __('Rent Report') }}
                        </a>
                    </li>
                    <li class="dash-item {{ Request::segment(1) == 'reports_rent_monthly' ? 'active' : '' }}">
                        <a href="{{ route('reports.rent.monthly') }}"
                            class="dash-link">{{ __('Monthly Rent Report') }}
                        </a>
                    </li>
                    <li class="dash-item {{ Request::segment(1) == 'stock_report' ? 'active' : '' }}">
                        <a href="{{ route('subproduct.stock_report') }}"
                            class="dash-link">{{ __('Stock Report') }}</a>
                    </li>
                    <li class="dash-item {{ Request::segment(1) == 'master-ledger' ? 'active' : '' }}">
                        <a href="{{ route('master-ledger.index') }}"
                            class="dash-link">{{ __('Master List') }}</a>
                    </li>
                    <li
                        class="dash-item {{ Request::route()->getName() == 'alternative-parts.index' ? ' active' : '' }}">
                        <a href="{{ route('alternative-parts.index') }}"
                            class="dash-link">{{ __('Alternative Parts') }}</a>
                    </li>
                </ul>
            </li>
        @endif

        <!--------------------- End Products System ----------------------------------->


        <!--------------------- Start POs System ----------------------------------->
        @if (false && !empty($userPlan) && $userPlan->pos == 1)
            @if (Gate::check('manage warehouse') || Gate::check('manage pos') || Gate::check('manage print settings'))
                <li
                    class="dash-item dash-hasmenu {{ Request::segment(1) == 'warehouse'
                    || in_array(Request::route()->getName(), [
                        'pos.barcode',
                        'pos.report',
                        'pos.print',
                        'pos.show',
                        'warehouse-transfer.index',
                        'pos-print-setting',
                        'pos_product_refund.index',
                        'pos_product_refund.create',
                        'pos_product_refund.get_products_to_refund',
                        'pos_product_refund.store_products_refund',
                        'pos_product_refund.print',
                        'pos_product_refund.refundableItems',
                    ]) ? ' active dash-trigger' : '' }}">
                    <a href="#!" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><span
                            class="dash-micon"><i class="ti ti-layers-difference"></i></span><span
                            class="dash-mtext">{{ __('POS System') }}</span></a>
                    <ul
                        class="dash-submenu {{ Request::segment(1) == 'warehouse' || in_array(Request::route()->getName(), [
                            'pos.barcode',
                            'pos.print',
                            'pos.show',
                            'pos-print-setting',
                            'pos_product_refund.index',
                            'pos_product_refund.create',
                            'pos_product_refund.get_products_to_refund',
                            'pos_product_refund.store_products_refund',
                            'pos_product_refund.print',
                            'pos_product_refund.refundableItems',
                        ])
                            ? 'show'
                            : '' }}">
                        @can('manage warehouse')
                            <li
                                class="dash-item {{ Request::route()->getName() == 'warehouse.index' || Request::route()->getName() == 'warehouse.show' ? ' active' : '' }}">
                                <a class="dash-link" href="{{ route('warehouse.index') }}">{{ __('Warehouse') }}</a>
                            </li>
                        @endcan
                        
                        @canany(['create barcode', 'manage pos', 'print pos'])
                            <li
                                class="dash-item {{ Request::route()->getName() == 'pos.barcode' || Request::route()->getName() == 'pos.print' ? ' active' : '' }}">
                                <a class="dash-link" href="{{ route('pos.barcode') }}">{{ __('Print Barcode Ticket') }}</a>
                            </li>
                        @endcanany

                        @can('manage warehouse')
                            <li
                                class="dash-item {{ Request::route()->getName() == 'payment-methods.index' || Request::route()->getName() == 'warehouse.show' ? ' active' : '' }}">
                                <a class="dash-link"
                                    href="{{ route('payment-methods.index') }}">{{ __('payment method ') }}</a>
                            </li>
                        @endcan

                        @can('manage warehouse')
                            <li
                                class="dash-item {{ Request::route()->getName() == 'pricelist.index' || Request::route()->getName() == 'pricelist.show' ? ' active' : '' }}">
                                <a class="dash-link" href="{{ route('pricelist.index') }}">{{ __('PriceList') }}</a>
                            </li>
                        @endcan

                        @can('manage warehouse')
                            <li
                                class="dash-item {{ Request::route()->getName() == 'combo_offers.index' || Request::route()->getName() == 'combo_offers.show' ? ' active' : '' }}">
                                <a class="dash-link"
                                    href="{{ route('combo_offers.index') }}">{{ __('Combo Offers') }}</a>
                            </li>
                        @endcan
                        @can('manage warehouse')
                            <li
                                class="dash-item {{ Request::route()->getName() == 'vouchers.index' || Request::route()->getName() == 'vouchers.show' ? ' active' : '' }}">
                                <a class="dash-link" href="{{ route('vouchers.index') }}">{{ __('Vouchers') }}</a>
                            </li>
                        @endcan
                        @can('manage warehouse')
                            <li
                                class="dash-item {{ Request::route()->getName() == 'pos.productsStock' ? ' active' : '' }}">
                                <a class="dash-link" href="{{ route('pos.productsStock') }}">{{ __('Stock') }}</a>
                            </li>
                        @endcan
                        @can('manage warehouse')
                            <li
                                class="dash-item {{ in_array(Request::route()->getName(), [
                                    'pos_product_refund.index',
                                    'pos_product_refund.create',
                                    'pos_product_refund.get_products_to_refund',
                                    'pos_product_refund.store_products_refund',
                                    'pos_product_refund.print',
                                    'pos_product_refund.refundableItems',
                                ]) ? ' active' : '' }}">
                                <a class="dash-link" href="{{ route('pos_product_refund.create') }}">{{ __('POS Refund') }}</a>
                            </li>
                        @endcan
                        {{--
                            @can('manage warehouse')
                                <li
                                    class="dash-item" {{ Request::route()->getName() == 'pos.productsStock' ? ' active' : '' }}>
                                    <a class="dash-link" href="{{ route('pos.productsStock') }}">{{ __('Refund') }}</a>
                                </li>
                            @endcan 
                        --}}
                        
                        @can('manage pos')
                            <li class="dash-item {{ Request::route()->getName() == 'pos.index' ? ' active' : '' }}">
                                <a class="dash-link" href="{{ route('pos.index') }}">{{ __(' Add POS') }}</a>
                            </li>
                            <li
                                class="dash-item {{ Request::route()->getName() == 'pos.report' || Request::route()->getName() == 'pos.show' ? ' active' : '' }}">
                                <a class="dash-link" href="{{ route('pos.report') }}">{{ __('POS') }}</a>
                            </li>
                        @endcan
                        @can('manage warehouse')
                            <li
                                class="dash-item {{ Request::route()->getName() == 'warehouse-transfer.index' || Request::route()->getName() == 'warehouse-transfer.show' ? ' active' : '' }}">
                                <a class="dash-link"
                                    href="{{ route('warehouse-transfer.index') }}">{{ __('Transfer') }}</a>
                            </li>
                        @endcan
                        @can('manage pos')
                            <li
                                class="dash-item {{ Request::route()->getName() == 'pos-print-setting' ? ' active' : '' }}">
                                <a class="dash-link"
                                    href="{{ route('pos.print.setting') }}">{{ __('Print Settings') }}</a>
                            </li>
                        @endcan

                    </ul>
                </li>
            @endif
        @endif
        <!--------------------- End POs System ----------------------------------->

        @if (\Auth::user()->type != 'super admin')
            @unless ($motorHideSidebarExtras)
            <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'support' ? 'active' : '' }}">
                <a href="{{ route('support.index') }}" class="dash-link">
                    <span class="dash-micon"><i class="ti ti-headphones"></i></span><span
                        class="dash-mtext">{{ __('Support System') }}</span>
                </a>
            </li>
            <li
                class="dash-item dash-hasmenu {{ Request::segment(1) == 'zoom-meeting' || Request::segment(1) == 'zoom-meeting-calender' ? 'active' : '' }}">
                <a href="{{ route('zoom-meeting.index') }}" class="dash-link">
                    <span class="dash-micon"><i class="ti ti-user-check"></i></span><span
                        class="dash-mtext">{{ __('Zoom Meeting') }}</span>
                </a>
            </li>
            <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'chats' ? 'active' : '' }}">
                <a href="{{ url('chats') }}" class="dash-link">
                    <span class="dash-micon"><i class="ti ti-message-circle"></i></span><span
                        class="dash-mtext">{{ __('Messenger') }}</span>
                </a>
            </li>
            @endunless
        @endif

        @if (\Auth::user()->type == 'company')
            @unless ($motorHideSidebarExtras)
            <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'notification_templates' ? 'active' : '' }}">
                <a href="{{ route('notification-templates.index') }}" class="dash-link">
                    <span class="dash-micon"><i class="ti ti-notification"></i></span><span
                        class="dash-mtext">{{ __('Notification Template') }}</span>
                </a>
            </li>
            @endunless
        @endif

        <!--------------------- Start System Setup ----------------------------------->

        @if (\Auth::user()->type != 'super admin')
            @if (Gate::check('manage company settings') ||
                    (!$motorHideSidebarExtras &&
                        (Gate::check('manage company plan') || Gate::check('manage order'))))
                <li
                    class="dash-item dash-hasmenu {{ Request::segment(1) == 'settings' ||
                    (!$motorHideSidebarExtras &&
                        (Request::segment(1) == 'plans' ||
                            Request::segment(1) == 'stripe' ||
                            Request::segment(1) == 'order'))
                        ? ' active dash-trigger'
                        : '' }}">
                    <a href="#!" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <span class="dash-micon"><i class="ti ti-settings"></i></span><span
                            class="dash-mtext">{{ __('Settings') }}</span>

                    </a>
                    <ul class="dash-submenu">
                        @if (Gate::check('manage company settings'))
                            <li class="dash-item  {{ Request::segment(1) == 'settings' ? ' active' : '' }}">
                                <a href="{{ route('settings') }}"
                                    class="dash-link">{{ __('System Settings') }}</a>
                            </li>
                        @endif
                        @if (!$motorHideSidebarExtras && Gate::check('manage company plan'))
                            <li
                                class="dash-item{{ Request::route()->getName() == 'plans.index' || Request::route()->getName() == 'stripe' ? ' active' : '' }}">
                                <a href="{{ route('plans.index') }}"
                                    class="dash-link">{{ __('Setup Subscription Plan') }}</a>
                            </li>
                        @endif

                        @if (!$motorHideSidebarExtras && Gate::check('manage order') && Auth::user()->type == 'company')
                            <li class="dash-item {{ Request::segment(1) == 'order' ? 'active' : '' }}">
                                <a href="{{ route('order.index') }}" class="dash-link">{{ __('Order') }}</a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
        @endif




        <!--------------------- End System Setup ----------------------------------->
        </ul>
        @endif
        @if (\Auth::user()->type == 'client')
            <ul class="dash-navbar">
                @if (Gate::check('manage client dashboard'))
                    <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'dashboard' ? ' active' : '' }}">
                        <a href="{{ route('client.dashboard.view') }}" class="dash-link">
                            <span class="dash-micon"><i class="ti ti-home"></i></span><span
                                class="dash-mtext">{{ __('Dashboard') }}</span>
                        </a>
                    </li>
                @endif
                @if (Gate::check('manage deal'))
                    <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'deals' ? ' active' : '' }}">
                        <a href="{{ route('deals.list') }}" class="dash-link">
                            <span class="dash-micon"><i class="ti ti-rocket"></i></span><span
                                class="dash-mtext">{{ __('Deals') }}</span>
                        </a>
                    </li>
                @endif
                @if (Gate::check('manage contract'))
                    <li
                        class="dash-item dash-hasmenu {{ Request::route()->getName() == 'contract.index' || Request::route()->getName() == 'contract.show' ? 'active' : '' }}">
                        <a href="{{ route('contract.index') }}" class="dash-link">
                            <span class="dash-micon"><i class="ti ti-rocket"></i></span><span
                                class="dash-mtext">{{ __('Contract') }}</span>
                        </a>
                    </li>
                @endif
                @unless ($motorHideSidebarExtras)
                @if (Gate::check('manage project'))
                    <li class="dash-item dash-hasmenu  {{ Request::segment(1) == 'projects' ? ' active' : '' }}">
                        <a href="{{ route('projects.index') }}" class="dash-link">
                            <span class="dash-micon"><i class="ti ti-share"></i></span><span
                                class="dash-mtext">{{ __('Project') }}</span>
                        </a>
                    </li>
                @endif
                @if (Gate::check('manage project'))
                    <li
                        class="dash-item  {{ Request::route()->getName() == 'project_report.index' || Request::route()->getName() == 'project_report.show' ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('project_report.index') }}">
                            <span class="dash-micon"><i class="ti ti-chart-line"></i></span><span
                                class="dash-mtext">{{ __('Project Report') }}</span>
                        </a>
                    </li>
                @endif

                @if (Gate::check('manage project task'))
                    <li class="dash-item dash-hasmenu  {{ Request::segment(1) == 'taskboard' ? ' active' : '' }}">
                        <a href="{{ route('taskBoard.view', 'list') }}" class="dash-link">
                            <span class="dash-micon"><i class="ti ti-list-check"></i></span><span
                                class="dash-mtext">{{ __('Tasks') }}</span>
                        </a>
                    </li>
                @endif

                @if (Gate::check('manage bug report'))
                    <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'bugs-report' ? ' active' : '' }}">
                        <a href="{{ route('bugs.view', 'list') }}" class="dash-link">
                            <span class="dash-micon"><i class="ti ti-bug"></i></span><span
                                class="dash-mtext">{{ __('Bugs') }}</span>
                        </a>
                    </li>
                @endif

                @if (Gate::check('manage timesheet'))
                    <li
                        class="dash-item dash-hasmenu {{ Request::segment(1) == 'timesheet-list' ? ' active' : '' }}">
                        <a href="{{ route('timesheet.list') }}" class="dash-link">
                            <span class="dash-micon"><i class="ti ti-clock"></i></span><span
                                class="dash-mtext">{{ __('Timesheet') }}</span>
                        </a>
                    </li>
                @endif

                @if (Gate::check('manage project task'))
                    <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'calendar' ? ' active' : '' }}">
                        <a href="{{ route('task.calendar', ['all']) }}" class="dash-link">
                            <span class="dash-micon"><i class="ti ti-calendar"></i></span><span
                                class="dash-mtext">{{ __('Task Calender') }}</span>
                        </a>
                    </li>
                @endif

                <li class="dash-item dash-hasmenu">
                    <a href="{{ route('support.index') }}"
                        class="dash-link {{ Request::segment(1) == 'support' ? 'active' : '' }}">
                        <span class="dash-micon"><i class="ti ti-headphones"></i></span><span
                            class="dash-mtext">{{ __('Support') }}</span>
                    </a>
                </li>
                @endunless
            </ul>
        @endif
        @if (\Auth::user()->type == 'super admin')
            <ul class="dash-navbar">
                @if (Gate::check('manage super admin dashboard'))
                    <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'dashboard' ? ' active' : '' }}">
                        <a href="{{ route('client.dashboard.view') }}" class="dash-link">
                            <span class="dash-micon"><i class="ti ti-home"></i></span><span
                                class="dash-mtext">{{ __('Dashboard') }}</span>
                        </a>
                    </li>
                @endif


                @can('manage user')
                    <li
                        class="dash-item dash-hasmenu {{ Request::route()->getName() == 'users.index' || Request::route()->getName() == 'users.create' || Request::route()->getName() == 'users.edit' ? ' active' : '' }}">
                        <a href="{{ route('users.index') }}" class="dash-link">
                            <span class="dash-micon"><i class="ti ti-users"></i></span><span
                                class="dash-mtext">{{ __('Companies') }}</span>
                        </a>
                    </li>
                @endcan

                @unless ($motorHideSidebarExtras)
                @if (Gate::check('manage plan'))
                    <li class="dash-item dash-hasmenu  {{ Request::segment(1) == 'plans' ? 'active' : '' }}">
                        <a href="{{ route('plans.index') }}" class="dash-link">
                            <span class="dash-micon"><i class="ti ti-trophy"></i></span><span
                                class="dash-mtext">{{ __('Plan') }}</span>
                        </a>
                    </li>
                @endif
                @if (\Auth::user()->type == 'super admin')
                    <li class="dash-item dash-hasmenu {{ request()->is('plan_request*') ? 'active' : '' }}">
                        <a href="{{ route('plan_request.index') }}" class="dash-link">
                            <span class="dash-micon"><i class="ti ti-arrow-up-right-circle"></i></span><span
                                class="dash-mtext">{{ __('Plan Request') }}</span>
                        </a>
                    </li>
                @endif
                @endunless
                @if (Gate::check('manage coupon'))
                    <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'coupons' ? 'active' : '' }}">
                        <a href="{{ route('coupons.index') }}" class="dash-link">
                            <span class="dash-micon"><i class="ti ti-gift"></i></span><span
                                class="dash-mtext">{{ __('Coupon') }}</span>
                        </a>
                    </li>
                @endif
                @unless ($motorHideSidebarExtras)
                @if (Gate::check('manage order'))
                    <li class="dash-item dash-hasmenu  {{ Request::segment(1) == 'orders' ? 'active' : '' }}">
                        <a href="{{ route('order.index') }}" class="dash-link">
                            <span class="dash-micon"><i class="ti ti-shopping-cart-plus"></i></span><span
                                class="dash-mtext">{{ __('Order') }}</span>
                        </a>
                    </li>
                @endif
                @endunless
                <li
                    class="dash-item dash-hasmenu {{ Request::segment(1) == 'email_template' || Request::route()->getName() == 'manage.email.language' ? ' active dash-trigger' : 'collapsed' }}">
                    <a href="{{ route('manage.email.language', [$emailTemplate->id, \Auth::user()->lang]) }}"
                        data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <span class="dash-micon"><i class="ti ti-template"></i></span>
                        <span class="dash-mtext">{{ __('Email Template') }}</span>
                    </a>
                </li>

                @if (\Auth::user()->type == 'super admin')
                    {{-- @include('landingpage::menu.landingpage') --}}
                @endif

                @if (Gate::check('manage system settings'))
                    <li
                        class="dash-item dash-hasmenu {{ Request::route()->getName() == 'systems.index' ? ' active' : '' }}">
                        <a href="{{ route('systems.index') }}" class="dash-link">
                            <span class="dash-micon"><i class="ti ti-settings"></i></span><span
                                class="dash-mtext">{{ __('Settings') }}</span>
                        </a>
                    </li>
                @endif

            </ul>
        @endif


        {{-- <div class="navbar-footer border-top ">
                    <div class="d-flex align-items-center py-3 px-3 border-bottom">
                        <div class="me-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="29" height="30" viewBox="0 0 29 30"
                                fill="none">
                                <circle cx="14.5" cy="15.1846" r="14.5" fill="#6FD943"></circle>
                                <path opacity="0.4"
                                    d="M22.08 8.66459C21.75 8.28459 21.4 7.92459 21.02 7.60459C19.28 6.09459 17 5.18461 14.5 5.18461C12.01 5.18461 9.73999 6.09459 7.98999 7.60459C7.60999 7.92459 7.24999 8.28459 6.92999 8.66459C5.40999 10.4146 4.5 12.6946 4.5 15.1846C4.5 17.6746 5.40999 19.9546 6.92999 21.7046C7.24999 22.0846 7.60999 22.4446 7.98999 22.7646C9.73999 24.2746 12.01 25.1846 14.5 25.1846C17 25.1846 19.28 24.2746 21.02 22.7646C21.4 22.4446 21.75 22.0846 22.08 21.7046C23.59 19.9546 24.5 17.6746 24.5 15.1846C24.5 12.6946 23.59 10.4146 22.08 8.66459ZM14.5 19.6246C13.54 19.6246 12.65 19.3146 11.93 18.7946C11.52 18.5146 11.17 18.1646 10.88 17.7546C10.37 17.0346 10.06 16.1346 10.06 15.1846C10.06 14.2346 10.37 13.3346 10.88 12.6146C11.17 12.2046 11.52 11.8546 11.93 11.5746C12.65 11.0546 13.54 10.7446 14.5 10.7446C15.46 10.7446 16.35 11.0546 17.08 11.5646C17.49 11.8546 17.84 12.2046 18.13 12.6146C18.64 13.3346 18.95 14.2346 18.95 15.1846C18.95 16.1346 18.64 17.0346 18.13 17.7546C17.84 18.1646 17.49 18.5146 17.08 18.8046C16.35 19.3146 15.46 19.6246 14.5 19.6246Z"
                                    fill="#162C4E"></path>
                                <path
                                    d="M22.08 8.66459L18.18 12.5746C18.16 12.5846 18.15 12.6046 18.13 12.6146C17.84 12.2046 17.49 11.8546 17.08 11.5646C17.09 11.5446 17.1 11.5346 17.12 11.5146L21.02 7.60459C21.4 7.92459 21.75 8.28459 22.08 8.66459Z"
                                    fill="#162C4E"></path>
                                <path
                                    d="M11.9297 18.7947C11.9197 18.8147 11.9097 18.8347 11.8897 18.8547L7.98969 22.7647C7.60969 22.4447 7.24969 22.0847 6.92969 21.7047L10.8297 17.7947C10.8397 17.7747 10.8597 17.7647 10.8797 17.7547C11.1697 18.1647 11.5197 18.5147 11.9297 18.7947Z"
                                    fill="#162C4E"></path>
                                <path
                                    d="M11.9297 11.5746C11.5197 11.8546 11.1697 12.2045 10.8797 12.6145C10.8597 12.6045 10.8497 12.5846 10.8297 12.5746L6.92969 8.66453C7.24969 8.28453 7.60969 7.92453 7.98969 7.60453L11.8897 11.5146C11.9097 11.5346 11.9197 11.5546 11.9297 11.5746Z"
                                    fill="#162C4E"></path>
                                <path
                                    d="M22.08 21.7046C21.75 22.0846 21.4 22.4446 21.02 22.7646L17.12 18.8546C17.1 18.8346 17.09 18.8246 17.08 18.8046C17.49 18.5146 17.84 18.1646 18.13 17.7546C18.15 17.7646 18.16 17.7746 18.18 17.7946L22.08 21.7046Z"
                                    fill="#162C4E"></path>
                            </svg>
                        </div>
                        <div>
                            <b class="d-block f-w-700">{{ __('You need help?') }}</b>
                            <span>{{ __('Check out our repository') }} </span>
                        </div>
                    </div>
                </div> --}}

    </div>
</div>
</nav>
<div id="sidebar-overlay" aria-hidden="true"></div>
