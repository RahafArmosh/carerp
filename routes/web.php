<?php

use App\Http\Controllers\AamarpayController;
use App\Http\Controllers\AiTemplateController;
use App\Http\Controllers\AllowanceController;
use App\Http\Controllers\AllowanceOptionController;
use App\Http\Controllers\AltPartNumberController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AppraisalController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AttendanceEmployeeController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\AwardTypeController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BankTransferController;
use App\Http\Controllers\BankTransferPaymentController;
use App\Http\Controllers\BenefitPaymentController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BugStatusController;
use App\Http\Controllers\CashfreeController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\ChartOfAccountTypeController;
use App\Http\Controllers\ChartOfAccountSubTypeController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CoingatePaymentController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\CompanyPolicyController;
use App\Http\Controllers\CompetenciesController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractTypeController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\CustomQuestionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\DebitNoteController;
use App\Http\Controllers\DeductionOptionController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DucumentUploadController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeePaymentController;
use App\Http\Controllers\TaskManagerController;
use App\Http\Controllers\TaskMasterController;
use App\Http\Controllers\EmployeeDailyTaskController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FlutterwavePaymentController;
use App\Http\Controllers\FormBuilderController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\GoalTrackingController;
use App\Http\Controllers\GoalTypeController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\InterviewScheduleController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\RentInvoiceController;
use App\Http\Controllers\IyziPayController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobCategoryController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobStageController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadStageController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\DailyLeaveController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanOptionController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MercadoPaymentController;
use App\Http\Controllers\MolliePaymentController;
use App\Http\Controllers\NotificationTemplatesController;
use App\Http\Controllers\OtherPaymentController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\PayFastController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CustomerPaymentController;
use App\Http\Controllers\CustomerRefundController;
use App\Http\Controllers\SalikAccountController;
use App\Http\Controllers\PaymentWallPaymentController;
use App\Http\Controllers\PaypalController;
use App\Http\Controllers\PaySlipController;
use App\Http\Controllers\PayslipTypeController;
use App\Http\Controllers\PaystackPaymentController;
use App\Http\Controllers\PaytabController;
use App\Http\Controllers\PaytmPaymentController;
use App\Http\Controllers\PaytrController;
use App\Http\Controllers\YooKassaController;
use App\Http\Controllers\PerformanceTypeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PlanRequestController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductServiceCategoryController;
use App\Http\Controllers\ProductServiceController;
use App\Http\Controllers\ProductServiceUnitController;
use App\Http\Controllers\ProductStockController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectReportController;
use App\Http\Controllers\ProjectstagesController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\AdvanceSaleOrderController;
use App\Http\Controllers\SaleOrderController;
use App\Http\Controllers\PickListController;
use App\Http\Controllers\PackingListController;
use App\Http\Controllers\RazorpayPaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResignationController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaturationDeductionController;
use App\Http\Controllers\SetSalaryController;
use App\Http\Controllers\SkrillPaymentController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\SspayController;
use App\Http\Controllers\StageController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\TaskStageController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\TerminationController;
use App\Http\Controllers\TerminationTypeController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\TimeTrackerController;
use App\Http\Controllers\ToyyibpayController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\TravelController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VenderController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseStockCountImportController;
use App\Http\Controllers\WarehouseTransferController;
use App\Http\Controllers\WarningController;
use App\Http\Controllers\ZoomMeetingController;
use App\Http\Controllers\XenditPaymentController;
use App\Http\Controllers\MidtransPaymentController;
use App\Http\Controllers\SubProductController;
use App\Http\Controllers\SimpleExpenseController;
use App\Http\Controllers\SimpleExpensePaymentController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\SubBrandController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PriceListRoleController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\XSS;
use App\Http\Middleware\RevalidateBackHistory;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\GoogleAdsLeadController;
use App\Http\Controllers\LeadRoleController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ComboOfferController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\VouchersController;
use App\Http\Controllers\WarehousePriceListController;
use App\Http\Controllers\CarManufacturerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PosProductsRefundController;
use App\Http\Controllers\DealReminderController;
use App\Http\Controllers\MasterlistLedgerController;
use App\Http\Controllers\PricingListsController;
use App\Http\Controllers\PricingListTypeController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\TrainingTypeController;
use App\Models\PosProductsRefund;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth'])->name('dashboard');

require __DIR__ . '/auth.php';




Route::get('/pos/productsStock', [PosController::class, 'productsStock'])->name('pos.productsStock')->middleware(['auth']);


///copy link
Route::get('/customer/invoice/{id}/', [InvoiceController::class, 'invoiceLink'])->name('invoice.link.copy');
Route::get('/vender/bill/{id}/', [BillController::class, 'invoiceLink'])->name('bill.link.copy');
Route::get('/vendor/purchase/{id}/', [PurchaseController::class, 'purchaseLink'])->name('purchase.link.copy');
Route::get('/customer/proposal/{id}/', [ProposalController::class, 'invoiceLink'])->name('proposal.link.copy');
Route::get('proposal/pdf/{id}/{type}', [ProposalController::class, 'proposal'])->name('proposal.pdf')->middleware([XSS::class, RevalidateBackHistory::class]);

//================================= Invoice Payment Gateways  ====================================//

Route::post('/customer-pay-with-bank', [BankTransferPaymentController::class, 'customerPayWithBank'])->name('customer.pay.with.bank')->middleware([XSS::class]);
Route::get('invoice/{id}/action', [BankTransferPaymentController::class, 'invoiceAction'])->name('invoice.action');
Route::post('invoice/{id}/changeaction', [BankTransferPaymentController::class, 'invoiceChangeStatus'])->name('invoice.changestatus');

Route::post('{id}/pay-with-paypal', [PaypalController::class, 'customerPayWithPaypal'])->name('customer.pay.with.paypal');
Route::get('{id}/get-payment-status/{amount}', [PaypalController::class, 'customerGetPaymentStatus'])->name('customer.get.payment.status')->middleware([XSS::class]);

Route::post('/customer-pay-with-paystack', [PaystackPaymentController::class, 'customerPayWithPaystack'])->name('customer.pay.with.paystack')->middleware([XSS::class]);
Route::get('/customer/paystack/{pay_id}/{invoice_id}', [PaystackPaymentController::class, 'getInvoicePaymentStatus'])->name('customer.paystack');

Route::post('/customer-pay-with-paytm', [PaytmPaymentController::class, 'customerPayWithPaytm'])->name('customer.pay.with.paytm')->middleware([XSS::class]);
Route::post('/customer/paytm/{invoice}/{amount}', [PaytmPaymentController::class, 'getInvoicePaymentStatus'])->name('customer.paytm');

Route::post('/customer-pay-with-flaterwave', [FlutterwavePaymentController::class, 'customerPayWithFlutterwave'])->name('customer.pay.with.flaterwave')->middleware([XSS::class]);
Route::get('/customer/flaterwave/{txref}/{invoice_id}', [FlutterwavePaymentController::class, 'getInvoicePaymentStatus'])->name('customer.flaterwave');

Route::post('/customer-pay-with-razorpay', [RazorpayPaymentController::class, 'customerPayWithRazorpay'])->name('customer.pay.with.razorpay')->middleware([XSS::class]);
Route::get('/customer/razorpay/{txref}/{invoice_id}', [RazorpayPaymentController::class, 'getInvoicePaymentStatus'])->name('customer.razorpay');

Route::post('/customer-pay-with-mercado', [MercadoPaymentController::class, 'customerPayWithMercado'])->name('customer.pay.with.mercado')->middleware([XSS::class]);
Route::get('/customer/mercado/{invoice}', [MercadoPaymentController::class, 'getInvoicePaymentStatus'])->name('customer.mercado');

Route::post('/customer-pay-with-mollie', [MolliePaymentController::class, 'customerPayWithMollie'])->name('customer.pay.with.mollie')->middleware([XSS::class]);
Route::get('/customer/mollie/{invoice}/{amount}', [MolliePaymentController::class, 'getInvoicePaymentStatus'])->name('customer.mollie');

Route::post('/customer-pay-with-skrill', [SkrillPaymentController::class, 'customerPayWithSkrill'])->name('customer.pay.with.skrill')->middleware([XSS::class]);
Route::get('/customer/skrill/{invoice}/{amount}', [SkrillPaymentController::class, 'getInvoicePaymentStatus'])->name('customer.skrill');

Route::post('/customer-pay-with-coingate', [CoingatePaymentController::class, 'customerPayWithCoingate'])->name('customer.pay.with.coingate')->middleware([XSS::class]);
Route::get('/customer/coingate/{invoice}/{amount}', [CoingatePaymentController::class, 'getInvoicePaymentStatus'])->name('customer.coingate');

Route::post('/paymentwall', [PaymentWallPaymentController::class, 'invoicepaymentwall'])->name('invoice.paymentwallpayment')->middleware([XSS::class]);
Route::post('/invoice-pay-with-paymentwall/{invoice}', [PaymentWallPaymentController::class, 'invoicePayWithPaymentwall'])->name('invoice.pay.with.paymentwall')->middleware([XSS::class]);
Route::get('/invoices/{flag}/{invoice}', [PaymentWallPaymentController::class, 'invoiceerror'])->name('error.invoice.show');

Route::post('/customer-pay-with-toyyibpay', [ToyyibpayController::class, 'invoicepaywithtoyyibpay'])->name('customer.pay.with.toyyibpay');
Route::get('/customer/toyyibpay/{invoice}/{amount}', [ToyyibpayController::class, 'getInvoicePaymentStatus'])->name('customer.toyyibpay');

Route::post('invoice-with-payfast', [PayFastController::class, 'invoicePayWithPayFast'])->name('invoice.with.payfast');
Route::get('invoice-payfast-status/{success}', [PayFastController::class, 'invoicepayfaststatus'])->name('invoice.payfast.status');

Route::post('/customer-pay-with-iyzipay', [IyziPayController::class, 'invoicepaywithiyzipay'])->name('customer.pay.with.iyzipay');
Route::post('iyzipay/callback/{invoice}/{amount}', [IyzipayController::class, 'getInvoiceiyzipayCallback'])->name('iyzipay.invoicepayment.callback');

Route::post('/customer-pay-with-sspay', [SspayController::class, 'invoicepaywithsspaypay'])->name('customer.pay.with.sspay');
Route::get('/customer/sspay/{invoice}/{amount}', [SspayController::class, 'getInvoicePaymentStatus'])->name('customer.sspay');

Route::post('/invoice-pay-with-paytab', [PaytabController::class, 'invoicePayWithpaytab'])->name('customer.pay.with.paytab');
Route::any('/invoice-paytab-success/{invoice}', [PaytabController::class, 'getInvoicePaymentStatus'])->name('invoice.paytab.success');

Route::any('invoice-with-benefit', [BenefitPaymentController::class, 'invoicepaywithbenefit'])->name('invoice.benefit.initiate');
Route::any('/invoice/benefit/{invoice_id}/{amount}', [BenefitPaymentController::class, 'getInvoicePaymentStatus'])->name('invoice.benefit.call_back');

Route::post('invoice-with-cashfree', [CashfreeController::class, 'invoicepaywithcashfree'])->name('customer.pay.with.cashfree');
Route::any('invoice-with-cashfree/cashfree', [CashfreeController::class, 'getInvoicePaymentStatus'])->name('invoice.cashfreePayment.success');

Route::post('invoice-with-aamarpay', [AamarpayController::class, 'invoicepaywithaamarpay'])->name('customer.pay.with.aamarpay');
Route::any('aamarpay-invoice/success/{data}', [AamarpayController::class, 'getInvoicePaymentStatus'])->name('invoice.pay.aamarpay.success');

Route::post('/invoice-with-paytr', [PaytrController::class, 'invoicepaywithpaytr'])->name('customer.pay.with.paytr');
Route::get('/invoice/paytr/status', [PaytrController::class, 'getInvoicePaymentStatus'])->name('invoice.paytr');

Route::post('invoice-with-yookassa/', [YooKassaController::class, 'invoicePayWithYookassa'])->name('customer.with.yookassa');
Route::any('invoice-yookassa-status/', [YooKassaController::class, 'getInvociePaymentStatus'])->name('invoice.yookassa.status');

Route::any('invoice-with-midtrans/', [MidtransPaymentController::class, 'invoicePayWithMidtrans'])->name('customer.with.midtrans');
Route::any('invoice-midtrans-status/', [MidtransPaymentController::class, 'getInvociePaymentStatus'])->name('invoice.midtrans.status');

Route::any('/invoice-with-xendit', [XenditPaymentController::class, 'invoicePayWithXendit'])->name('customer.with.xendit');
Route::any('/invoice-xendit-status', [XenditPaymentController::class, 'getInvociePaymentStatus'])->name('invoice.xendit.status');

/***********************************************************************************************************************************************/

//career page
Route::get('career/{id}/{lang}', [JobController::class, 'career'])->name('career')->middleware([XSS::class]);
Route::get('job/requirement/{code}/{lang}', [JobController::class, 'jobRequirement'])->name('job.requirement')->middleware([XSS::class]);
Route::get('job/apply/{code}/{lang}', [JobController::class, 'jobApply'])->name('job.apply')->middleware([XSS::class]);
Route::post('job/apply/data/{code}', [JobController::class, 'jobApplyData'])->name('job.apply.data')->middleware([XSS::class]);

//project copy module
Route::get('/projects/copylink/{id}', [ProjectController::class, 'projectCopyLink'])->name('projects.copylink')->middleware(['auth']);
Route::any('/projects/link/{id}/{lang?}', [ProjectController::class, 'projectlink'])->name('projects.link')->middleware(['auth', XSS::class]);
Route::get('timesheet-table-view', [TimesheetController::class, 'filterTimesheetTableView'])->name('filter.timesheet.table.view')->middleware(['auth', XSS::class]);

// Invoice Payment Gateways
Route::post('customer/{id}/payment', [StripePaymentController::class, 'addpayment'])->name('customer.payment');
Route::get('invoice/pdf/{id}', [InvoiceController::class, 'invoice'])->name('invoice.pdf')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
Route::get('invoice/ledger/{id}', [InvoiceController::class, 'invoice_ledger'])->name('invoice.ledger')->middleware(['auth', XSS::class]);
Route::get('users/{id}/login-with-company', [UserController::class, 'LoginWithCompany'])->name('login.with.company')->middleware(['auth']);
Route::get('login-with-company/exit', [UserController::class, 'ExitCompany'])->name('exit.company')->middleware(['auth']);

Route::get('/form/{code}', [FormBuilderController::class, 'formView'])->name('form.view')->middleware([XSS::class]);
Route::post('/form_view_store', [FormBuilderController::class, 'formViewStore'])->name('form.view.store')->middleware([XSS::class]);

Route::get('/', [DashboardController::class, 'landingpage'])->middleware([XSS::class, RevalidateBackHistory::class]);

//================================= Invoice Payment Gateways  ====================================//
Route::group(['middleware' => ['verified']], function () {

    Route::get('/home', [DashboardController::class, 'account_dashboard_index'])->name('home')->middleware([XSS::class, RevalidateBackHistory::class]);

    Route::get('/account-dashboard', [DashboardController::class, 'account_dashboard_index'])->name('dashboard')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('/stock-overview', [DashboardController::class, 'stockOverview'])->name('stock.overview')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('/stock-overview/export', [DashboardController::class, 'stockOverviewExport'])->name('stock.overview.export')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('/sell-overview', [DashboardController::class, 'sellOverview'])->name('sell.overview')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('/sell-overview/details', [DashboardController::class, 'sellOverviewDetails'])->name('sell.overview.details')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('/sell-overview/export', [DashboardController::class, 'sellOverviewExport'])->name('sell.overview.export')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('/project-dashboard', [DashboardController::class, 'project_dashboard_index'])->name('project.dashboard')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('/hrm-dashboard', [DashboardController::class, 'hrm_dashboard_index'])->name('hrm.dashboard')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('/crm-dashboard', [DashboardController::class, 'crm_dashboard_index'])->name('crm.dashboard')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('/pos-dashboard', [DashboardController::class, 'pos_dashboard_index'])->name('pos.dashboard')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::get('profile', [UserController::class, 'profile'])->name('profile')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::any('edit-profile', [UserController::class, 'editprofile'])->name('update.account')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::resource('users', UserController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::post('change-password', [UserController::class, 'updatePassword'])->name('update.password');

    Route::any('user-reset-password/{id}', [UserController::class, 'userPassword'])->name('users.reset');

    Route::post('user-reset-password/{id}', [UserController::class, 'userPasswordReset'])->name('user.password.update');
    Route::get('company-info/{id}', [UserController::class, 'companyInfo'])->name('company.info');
    Route::post('user-unable', [UserController::class, 'userUnable'])->name('user.unable');

    Route::get('/change/mode', [UserController::class, 'changeMode'])->name('change.mode');

    Route::resource('roles', RoleController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::resource('permissions', PermissionController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::get('change-language/{lang}', [LanguageController::class, 'changeLanquage'])->name('change.language');

            Route::get('manage-language/{lang}', [LanguageController::class, 'manageLanguage'])->name('manage.language');

            Route::post('store-language-data/{lang}', [LanguageController::class, 'storeLanguageData'])->name('store.language.data');

            Route::get('create-language', [LanguageController::class, 'createLanguage'])->name('create.language');

            Route::any('store-language', [LanguageController::class, 'storeLanguage'])->name('store.language');

            Route::delete('/lang/{lang}', [LanguageController::class, 'destroyLang'])->name('lang.destroy');
        }
    );

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::resource('systems', SystemController::class);
            Route::post('email-settings', [SystemController::class, 'saveEmailSettings'])->name('email.settings');
            Route::post('company-email-settings', [SystemController::class, 'saveCompanyEmailSettings'])->name('company.email.settings');

            Route::post('company-settings', [SystemController::class, 'saveCompanySettings'])->name('company.settings');
            Route::post('system-settings', [SystemController::class, 'saveSystemSettings'])->name('system.settings');
            Route::post('zoom-settings', [SystemController::class, 'saveZoomSettings'])->name('zoom.settings');
            Route::post('tracker-settings', [SystemController::class, 'saveTrackerSettings'])->name('tracker.settings');
            Route::post('slack-settings', [SystemController::class, 'saveSlackSettings'])->name('slack.settings');
            Route::post('telegram-settings', [SystemController::class, 'saveTelegramSettings'])->name('telegram.settings');
            Route::post('twilio-settings', [SystemController::class, 'saveTwilioSettings'])->name('twilio.setting');
            Route::get('print-setting', [SystemController::class, 'printIndex'])->name('print.setting');
            Route::get('settings', [SystemController::class, 'companyIndex'])->name('settings');
            Route::post('business-setting', [SystemController::class, 'saveBusinessSettings'])->name('business.setting');
            Route::post('company-payment-setting', [SystemController::class, 'saveCompanyPaymentSettings'])->name('company.payment.settings');

            Route::get('test-mail', [SystemController::class, 'testMail'])->name('test.mail');
            Route::post('test-mail', [SystemController::class, 'testMail'])->name('test.mail');
            Route::post('test-mail/send', [SystemController::class, 'testSendMail'])->name('test.send.mail');

            Route::post('stripe-settings', [SystemController::class, 'savePaymentSettings'])->name('payment.settings');
            Route::post('pusher-setting', [SystemController::class, 'savePusherSettings'])->name('pusher.setting');
            Route::post('recaptcha-settings', [SystemController::class, 'recaptchaSettingStore'])->name('recaptcha.settings.store')->middleware(['auth', XSS::class]);

            Route::post('seo-settings', [SystemController::class, 'seoSettings'])->name('seo.settings.store')->middleware(['auth', XSS::class]);
            Route::any('webhook-settings', [SystemController::class, 'webhook'])->name('webhook.settings')->middleware(['auth', XSS::class]);
            Route::get('webhook-settings/create', [SystemController::class, 'webhookCreate'])->name('webhook.create')->middleware(['auth', XSS::class]);
            Route::post('webhook-settings/store', [SystemController::class, 'webhookStore'])->name('webhook.store');
            Route::get('webhook-settings/{wid}/edit', [SystemController::class, 'webhookEdit'])->name('webhook.edit')->middleware(['auth', XSS::class]);
            Route::post('webhook-settings/{wid}/edit', [SystemController::class, 'webhookUpdate'])->name('webhook.update')->middleware(['auth', XSS::class]);
            Route::delete('webhook-settings/{wid}', [SystemController::class, 'webhookDestroy'])->name('webhook.destroy')->middleware(['auth', XSS::class]);

            Route::post('cookie-setting', [SystemController::class, 'saveCookieSettings'])->name('cookie.setting');

            Route::post('cache-settings', [SystemController::class, 'cacheSettingStore'])->name('cache.settings.store')->middleware(['auth', XSS::class]);
        }
    );

    Route::get('productservice/index', [ProductServiceController::class, 'index'])->name('productservice.index');
    Route::get('productservice/{id}/brochure.pdf', [ProductServiceController::class, 'brochurePdf'])->name('productservice.brochure.pdf');
    Route::get('productservice/{id}/detail', [ProductServiceController::class, 'warehouseDetail'])->name('productservice.detail');
    Route::post('empty-cart', [ProductServiceController::class, 'emptyCart'])->middleware(['auth', XSS::class]);
    Route::post('warehouse-empty-cart', [ProductServiceController::class, 'warehouseemptyCart'])->name('warehouse-empty-cart')->middleware(['auth', XSS::class]);
    Route::resource('productservice', ProductServiceController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    //Product Stock
    Route::resource('productstock', ProductStockController::class)->middleware(['auth', XSS::class]);

    //Customer
    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::get('customer/{id}/show', [CustomerController::class, 'show'])->name('customer.show');
            Route::get('customer/search', [CustomerController::class, 'searchCustomers'])->name('customer.search');
            Route::resource('customer', CustomerController::class);
        }
    );

    //Vendor
    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::get('vender/{id}/show', [VenderController::class, 'show'])->name('vender.show');
            Route::resource('vender', VenderController::class);
        }
    );

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::resource('bank-account', BankAccountController::class);
        }
    );

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::get('bank-transfer/index', [BankTransferController::class, 'index'])->name('bank-transfer.index');
            Route::get('bank-transfer/print/{id}', [BankTransferController::class, 'print'])->name('bank-transfer.print')->middleware(['auth', XSS::class]);
            Route::resource('bank-transfer', BankTransferController::class);
        }
    );

    Route::resource('taxes', TaxController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::resource('product-category', ProductServiceCategoryController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('product-category/export', [ProductServiceCategoryController::class, 'export'])->name('product-category.export')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::post('product-category/getaccount', [ProductServiceCategoryController::class, 'getAccount'])->name('productServiceCategory.getaccount')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::resource('product-unit', ProductServiceUnitController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::get('invoice/{id}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoice.duplicate');
            Route::get('invoice/{id}/shipping/print', [InvoiceController::class, 'shippingDisplay'])->name('invoice.shipping.print');
            Route::get('invoice/{id}/print-grouped', [InvoiceController::class, 'printGrouped'])->name('invoice.print.grouped');
            Route::get('invoice/{id}/payment/reminder', [InvoiceController::class, 'paymentReminder'])->name('invoice.payment.reminder');
            Route::get('invoice/{id}/items/export', [InvoiceController::class, 'exportInvoiceItems'])->name('invoice.items.export');
            Route::get('invoice/index', [InvoiceController::class, 'index'])->name('invoice.index');
            Route::get('rentinvoice/rent-index', [InvoiceController::class, 'rent_index'])->name('rentinvoice.index');
            Route::post('invoice/product/destroy', [InvoiceController::class, 'productDestroy'])->name('invoice.product.destroy');
            Route::post('invoice/product', [InvoiceController::class, 'product'])->name('invoice.product');
            Route::post('invoice/customer', [InvoiceController::class, 'customer'])->name('invoice.customer');
            Route::get('invoice/{id}/sent', [InvoiceController::class, 'sent'])->name('invoice.sent');
            Route::get('invoice/{id}/resent', [InvoiceController::class, 'resent'])->name('invoice.resent');
            Route::get('invoice/{id}/payment', [InvoiceController::class, 'payment'])->name('invoice.payment');
            Route::get('invoice/{id}/userpayment', [InvoiceController::class, 'userPayment'])->name('invoice.userpayment');
            Route::post('invoice/{id}/payment', [InvoiceController::class, 'createPayment'])->name('invoice.payment');
            Route::post('invoice/{id}/payment/{pid}/destroy', [InvoiceController::class, 'paymentDestroy'])->name('invoice.payment.destroy');
            Route::get('invoice/{id}/approve', [InvoiceController::class, 'approve'])->name('invoice.approve');
            Route::get('invoice/{id}/notapprove', [InvoiceController::class, 'notapprove'])->name('invoice.notapprove');
            Route::get('invoice/{id}/backtoapprove', [InvoiceController::class, 'backtoapprove'])->name('invoice.backtoapprove');
            Route::get('invoice/{id}/receive', [InvoiceController::class, 'receive'])->name('invoice.receive');
            Route::get('invoice/{id}/sendtoapprove', [InvoiceController::class, 'sendtoapprove'])->name('invoice.sendtoapprove');
            Route::get('invoice/items', [InvoiceController::class, 'items'])->name('invoice.items');
            Route::resource('invoice', InvoiceController::class);
            //Route::resource('rentinvoice', InvoiceController::class);
            Route::get('rentinvoice/{id}', [InvoiceController::class, 'show'])->name('rentinvoice.show');
            Route::get('invoice/create/{type}', [InvoiceController::class, 'create'])->name('invoice.create');
            Route::get('rentinvoice/create/{type}', [RentInvoiceController::class, 'create'])->name('rentinvoice.create');
            // Route::get('rentinvoice/store/{cid}', [RentInvoiceController::class, 'store'])->name('rentinvoice.store');
        }
    );

    Route::get('/invoices/preview/{template}/{color}', [InvoiceController::class, 'previewInvoice'])->name('invoice.preview');
    Route::post('/invoices/template/setting', [InvoiceController::class, 'saveTemplateSettings'])->name('template.setting');

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::get('credit-note', [CreditNoteController::class, 'index'])->name('credit.note');
            Route::get('custom-credit-note', [CreditNoteController::class, 'customCreate'])->name('invoice.custom.credit.note');
            Route::post('custom-credit-note', [CreditNoteController::class, 'customStore'])->name('invoice.custom.credit.note');
            Route::get('credit-note/invoice', [CreditNoteController::class, 'getinvoice'])->name('invoice.get');
            Route::get('invoice/{id}/credit-note', [CreditNoteController::class, 'create'])->name('invoice.credit.note');
            Route::post('invoice/{id}/credit-note', [CreditNoteController::class, 'store'])->name('invoice.credit.note');
            Route::get('invoice/{id}/credit-note/edit/{cn_id}', [CreditNoteController::class, 'edit'])->name('invoice.edit.credit.note');
            Route::post('invoice/{id}/credit-note/edit/{cn_id}', [CreditNoteController::class, 'update'])->name('invoice.edit.credit.note');
            Route::delete('invoice/{id}/credit-note/delete/{cn_id}', [CreditNoteController::class, 'destroy'])->name('invoice.delete.credit.note');
        }
    );

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::get('debit-note', [DebitNoteController::class, 'index'])->name('debit.note');
            Route::get('custom-debit-note', [DebitNoteController::class, 'customCreate'])->name('bill.custom.debit.note');
            Route::post('custom-debit-note', [DebitNoteController::class, 'customStore'])->name('bill.custom.debit.note');
            Route::get('debit-note/bill', [DebitNoteController::class, 'getbill'])->name('bill.get');
            Route::get('bill/{id}/debit-note', [DebitNoteController::class, 'create'])->name('bill.debit.note');
            Route::post('bill/{id}/debit-note', [DebitNoteController::class, 'store'])->name('bill.debit.note');
            Route::get('bill/{id}/debit-note/edit/{cn_id}', [DebitNoteController::class, 'edit'])->name('bill.edit.debit.note');
            Route::post('bill/{id}/debit-note/edit/{cn_id}', [DebitNoteController::class, 'update'])->name('bill.edit.debit.note');
            Route::delete('bill/{id}/debit-note/delete/{cn_id}', [DebitNoteController::class, 'destroy'])->name('bill.delete.debit.note');
        }
    );

    Route::get('/bill/preview/{template}/{color}', [BillController::class, 'previewBill'])->name('bill.preview')->middleware(['auth', XSS::class]);
    Route::post('/bill/template/setting', [BillController::class, 'saveBillTemplateSettings'])->name('bill.template.setting');

    Route::resource('taxes', TaxController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::get('revenue/index', [RevenueController::class, 'index'])->name('revenue.index')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::resource('revenue', RevenueController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::get('bill/pdf/{id}', [BillController::class, 'bill'])->name('bill.pdf')->middleware([XSS::class, RevalidateBackHistory::class]);

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::get('bill/{id}/duplicate', [BillController::class, 'duplicate'])->name('bill.duplicate');
            Route::get('bill/{id}/shipping/print', [BillController::class, 'shippingDisplay'])->name('bill.shipping.print');
            Route::get('bill/index', [BillController::class, 'index'])->name('bill.index');
            Route::post('bill/product/destroy', [BillController::class, 'productDestroy'])->name('bill.product.destroy');
            Route::post('bill/product', [BillController::class, 'product'])->name('bill.product');
            Route::post('bill/vender', [BillController::class, 'vender'])->name('bill.vender');
            Route::post('bill/{id}/sent', [BillController::class, 'sent'])->name('bill.sent');
            Route::get('bill/{id}/approve', [BillController::class, 'approve'])->name('bill.approve');
            Route::get('bill/{id}/notapprove', [BillController::class, 'notapprove'])->name('bill.notapprove');
            Route::get('bill/{id}/backtoapprove', [BillController::class, 'backtoapprove'])->name('bill.backtoapprove');
            Route::get('bill/{id}/receive', [BillController::class, 'receive'])->name('bill.receive');
            Route::get('bill/{id}/sendtoapprove', [BillController::class, 'sendtoapprove'])->name('bill.sendtoapprove');
            Route::get('bill/{id}/resent', [BillController::class, 'resent'])->name('bill.resent');
            Route::get('bill/{id}/payment', [BillController::class, 'payment'])->name('bill.payment');
            Route::post('bill/{id}/payment', [BillController::class, 'createPayment'])->name('bill.payment');
            Route::post('bill/{id}/payment/{pid}/destroy', [BillController::class, 'paymentDestroy'])->name('bill.payment.destroy');
            Route::get('bill/items', [BillController::class, 'items'])->name('bill.items');
            Route::get('purchase-return', [PurchaseReturnController::class, 'index'])->name('purchase.return.index');
            Route::get('purchase-return/create', [PurchaseReturnController::class, 'create'])->name('purchase.return.create');
            Route::get('purchase-return/create-import', [PurchaseReturnController::class, 'createImport'])->name('purchase.return.create.import');
            Route::get('purchase-return/import-sample', [PurchaseReturnController::class, 'downloadImportSample'])->name('purchase.return.import.sample');
            Route::get('purchase-return/{id}/view', [PurchaseReturnController::class, 'show'])->name('purchase.return.show');
            Route::get('purchase-return/{id}/ledger', [PurchaseReturnController::class, 'purchase_return_ledger'])->name('purchase.return.ledger');
            Route::post('purchase-return', [PurchaseReturnController::class, 'store'])->name('purchase.return.store');
            Route::get('purchase-return/bill-items/{billId}', [PurchaseReturnController::class, 'billItems'])->name('purchase.return.bill.items');
            Route::post('purchase-return/import-items', [PurchaseReturnController::class, 'importBillItems'])->name('purchase.return.import.items');
            Route::get('sales-return', [SalesReturnController::class, 'index'])->name('sales.return.index');
            Route::get('sales-return/create', [SalesReturnController::class, 'create'])->name('sales.return.create');
            Route::get('sales-return/create-import', [SalesReturnController::class, 'createImport'])->name('sales.return.create.import');
            Route::get('sales-return/import-sample', [SalesReturnController::class, 'downloadImportSample'])->name('sales.return.import.sample');
            Route::get('sales-return/{id}/view', [SalesReturnController::class, 'show'])->name('sales.return.show');
            Route::get('sales-return/{id}/ledger', [SalesReturnController::class, 'sales_return_ledger'])->name('sales.return.ledger');
            Route::post('sales-return', [SalesReturnController::class, 'store'])->name('sales.return.store');
            Route::get('sales-return/invoice-items/{invoiceId}', [SalesReturnController::class, 'invoiceItems'])->name('sales.return.invoice.items');
            Route::post('sales-return/import-items', [SalesReturnController::class, 'importInvoiceItems'])->name('sales.return.import.items');
            Route::resource('bill', BillController::class);
            Route::get('bill/create/{cid}', [BillController::class, 'create'])->name('bill.create');
            Route::get('bill/create-from-request/{cid}', [BillController::class, 'createFromRequest'])->name('bill.createFromRequest');
            Route::post('vender/detail', [VenderController::class, 'getDetail'])->name('vender.detail');
            Route::post('currency/rate', [CurrencyController::class, 'getRate'])->name('currency.rate');
        }
    );

    Route::get('payment/index', [PaymentController::class, 'index'])->name('payment.index')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('refund/index', [RefundController::class, 'index'])->name('refund.index')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::resource('payment', PaymentController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::resource('refund', RefundController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::get('report/transaction', [TransactionController::class, 'index'])->name('transaction.index');
        }
    );

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::get('report/income-summary', [ReportController::class, 'incomeSummary'])->name('report.income.summary');
            Route::get('report/expense-summary', [ReportController::class, 'expenseSummary'])->name('report.expense.summary');
            Route::get('report/income-vs-expense-summary', [ReportController::class, 'incomeVsExpenseSummary'])->name('report.income.vs.expense.summary');
            Route::get('report/tax-summary', [ReportController::class, 'taxSummary'])->name('report.tax.summary');
            //        Route::get('report/profit-loss-summary', [ReportController::class, 'profitLossSummary'])->name('report.profit.loss.summary');
            Route::get('report/invoice-summary', [ReportController::class, 'invoiceSummary'])->name('report.invoice.summary');
            Route::get('report/bill-summary', [ReportController::class, 'billSummary'])->name('report.bill.summary');
            Route::get('report/product-stock-report', [ReportController::class, 'productStock'])->name('report.product.stock.report');
            Route::get('report/item-master', [ReportController::class, 'itemMaster'])->name('report.item.master');
            Route::get('report/invoice-report', [ReportController::class, 'invoiceReport'])->name('report.invoice');
            Route::get('report/account-statement-report', [ReportController::class, 'accountStatement'])->name('report.account.statement');
            Route::get('report/customer-statement-report', [ReportController::class, 'customerStatement'])->name('report.customer.statement');
            Route::get('report/customer-statement-report/pdf', [ReportController::class, 'customerStatementPdf'])->name('report.customer.statement.pdf');
            Route::get('export/customer-statement', [ReportController::class, 'exportCustomerStatement'])->name('report.customer.statement.export');
            Route::get('report/vendor-statement-report', [ReportController::class, 'vendorStatement'])->name('report.vendor.statement');
            Route::get('report/employee-statement-report', [ReportController::class, 'EmployeeStatement'])->name('report.employee.statement');
            Route::get('report/balance-sheet/{view?}', [ReportController::class, 'balanceSheet'])->name('report.balance.sheet');
            Route::get('report/company-tax', [ReportController::class, 'companyTaxReport'])->name('report.company.tax');
            Route::get('report/profit-loss/{view?}', [ReportController::class, 'profitLoss'])->name('report.profit.loss');

            Route::get('report/ledger/{account?}', [ReportController::class, 'ledgerSummary'])->name('report.ledger');
            Route::get('report/Gledger/{account?}', [ReportController::class, 'GledgerSummary'])->name('report.Gledger');
            Route::get('report/trial-balance', [ReportController::class, 'trialBalanceSummary'])->name('trial.balance');
            Route::get('report/trial-balance-total', [ReportController::class, 'trialBalanceSummaryTotal'])->name('trial.balance.total');

            Route::get('reports-monthly-cashflow', [ReportController::class, 'monthlyCashflow'])->name('report.monthly.cashflow')->middleware(['auth', XSS::class]);
            Route::get('reports-quarterly-cashflow', [ReportController::class, 'quarterlyCashflow'])->name('report.quarterly.cashflow')->middleware(['auth', XSS::class]);
            Route::post('export/trial-balance', [ReportController::class, 'trialBalanceExport'])->name('trial.balance.export');
            Route::post('export/trial-balance-total', [ReportController::class, 'trialBalanceTotoalExport'])->name('trial.balance.total.export');
            Route::post('export/balance-sheet', [ReportController::class, 'balanceSheetExport'])->name('balance.sheet.export');
            Route::post('export/profit-loss', [ReportController::class, 'profitLossExport'])->name('profit.loss.export');
            Route::get('report/sales', [ReportController::class, 'salesReport'])->name('report.sales');
            Route::post('export/sales', [ReportController::class, 'salesReportExport'])->name('sales.export');
            Route::post('print/sales-report', [ReportController::class, 'salesReportPrint'])->name('sales.report.print');
            Route::get('report/receivables', [ReportController::class, 'ReceivablesReport'])->name('report.receivables');
            Route::post('export/receivables', [ReportController::class, 'ReceivablesExport'])->name('receivables.export');
            Route::post('print/receivables', [ReportController::class, 'ReceivablesPrint'])->name('receivables.print');
            Route::get('report/payables', [ReportController::class, 'PayablesReport'])->name('report.payables');
            Route::post('print/payables', [ReportController::class, 'PayablesPrint'])->name('payables.print');
        }
    );

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::get('proposal/{id}/status/change', [ProposalController::class, 'statusChange'])->name('proposal.status.change');
            Route::get('proposal/{id}/convert', [ProposalController::class, 'convert'])->name('proposal.convert');
            Route::get('proposal/{id}/duplicate', [ProposalController::class, 'duplicate'])->name('proposal.duplicate');
            Route::post('proposal/product/destroy', [ProposalController::class, 'productDestroy'])->name('proposal.product.destroy');
            Route::post('proposal/customer', [ProposalController::class, 'customer'])->name('proposal.customer');
            Route::post('proposal/product', [ProposalController::class, 'product'])->name('proposal.product');
            Route::get('proposal/items', [ProposalController::class, 'items'])->name('proposal.items');
            Route::get('proposal/{id}/sent', [ProposalController::class, 'sent'])->name('proposal.sent');
            Route::get('proposal/{id}/resent', [ProposalController::class, 'resent'])->name('proposal.resent');
            Route::resource('proposal', ProposalController::class);
            Route::get('proposal/create/{cid}', [ProposalController::class, 'create'])->name('proposal.create');
            
            // Sale Order Routes
            Route::get('saleorder', [SaleOrderController::class, 'index'])->name('saleorder.index');
            Route::get('saleorder/import', [SaleOrderController::class, 'importFile'])->name('saleorder.import');
            Route::post('saleorder/import', [SaleOrderController::class, 'import'])->name('saleorder.import');
            Route::get('saleorder/import/items-only', [SaleOrderController::class, 'importFileItemsOnly'])->name('saleorder.import.items-only');
            Route::post('saleorder/import/items-only', [SaleOrderController::class, 'importItemsOnly'])->name('saleorder.import.items-only.store');
            Route::get('saleorder/sample', [SaleOrderController::class, 'downloadSample'])->name('saleorder.sample');
            Route::get('saleorder/sample/items-only', [SaleOrderController::class, 'downloadSampleItemsOnly'])->name('saleorder.sample.items-only');
            Route::post('saleorder/{id}/convert-to-picklist', [SaleOrderController::class, 'convertToPickList'])->name('saleorder.convert-to-picklist');
            Route::get('saleorder/{id}/convert-to-invoice', [SaleOrderController::class, 'convertToInvoice'])->name('saleorder.convert-to-invoice');
            Route::get('saleorder/{id}/print', [SaleOrderController::class, 'print'])->name('saleorder.print');
            Route::get('saleorder/{id}', [SaleOrderController::class, 'show'])->name('saleorder.show');
            Route::get('saleorder_master_list/{id}', [SaleOrderController::class, 'show2'])->name('saleorder.show2');
            
            Route::get('saleorder/{id}/edit', [SaleOrderController::class, 'edit'])->name('saleorder.edit');
            Route::put('saleorder/{id}', [SaleOrderController::class, 'update'])->name('saleorder.update');
            Route::put('saleorder/{id}/status', [SaleOrderController::class, 'updateStatus'])->name('saleorder.update-status');
            Route::delete('saleorder/{id}', [SaleOrderController::class, 'destroy'])->name('saleorder.destroy');

            // Advance Sale Order Routes
            Route::get('advance-saleorder', [AdvanceSaleOrderController::class, 'index'])->name('advance-saleorder.index');
            Route::get('advance-saleorder/import', [AdvanceSaleOrderController::class, 'importFile'])->name('advance-saleorder.import');
            Route::post('advance-saleorder/import', [AdvanceSaleOrderController::class, 'import'])->name('advance-saleorder.import');
            Route::get('advance-saleorder/sample/items-only', [AdvanceSaleOrderController::class, 'downloadSampleItemsOnly'])->name('advance-saleorder.sample.items-only');
            Route::get('advance-saleorder/{id}', [AdvanceSaleOrderController::class, 'show'])->name('advance-saleorder.show');
            Route::get('advance-saleorder/{id}/edit', [AdvanceSaleOrderController::class, 'edit'])->name('advance-saleorder.edit');
            Route::put('advance-saleorder/{id}', [AdvanceSaleOrderController::class, 'update'])->name('advance-saleorder.update');
            Route::delete('advance-saleorder/{id}', [AdvanceSaleOrderController::class, 'destroy'])->name('advance-saleorder.destroy');
            
            // Pick List Routes
            Route::get('picklist', [PickListController::class, 'index'])->name('picklist.index');
            Route::post('picklist/get-bin-location', [PickListController::class, 'getBinLocation'])->name('picklist.get-bin-location');
            Route::post('picklist/{id}/convert-to-packinglist', [PickListController::class, 'convertToPackingList'])->name('picklist.convert-to-packinglist');
            Route::get('picklist/{id}', [PickListController::class, 'show'])->name('picklist.show');
            Route::get('picklist/{id}/edit', [PickListController::class, 'edit'])->name('picklist.edit');
            Route::put('picklist/{id}', [PickListController::class, 'update'])->name('picklist.update');
            Route::put('picklist/{id}/status', [PickListController::class, 'updateStatus'])->name('picklist.update-status');
            Route::get('picklist/{id}/status-logs', [PickListController::class, 'statusLogs'])->name('picklist.status-logs');
            Route::post('picklist/{id}/item-tick', [PickListController::class, 'updateItemTick'])->name('picklist.item-tick');
            Route::post('picklist/{id}/assign', [PickListController::class, 'assign'])->name('picklist.assign');
            
            // Packing List Routes
            Route::get('packinglist', [PackingListController::class, 'index'])->name('packinglist.index');
            Route::get('packinglist/{id}', [PackingListController::class, 'show'])->name('packinglist.show');
            Route::get('packinglist/{id}/edit', [PackingListController::class, 'edit'])->name('packinglist.edit');
            Route::put('packinglist/{id}', [PackingListController::class, 'update'])->name('packinglist.update');
            Route::put('packinglist/{id}/status', [PackingListController::class, 'updateStatus'])->name('packinglist.update-status');
            Route::post('packinglist/{id}/generate-box', [PackingListController::class, 'generateBox'])->name('packinglist.generate-box');
            Route::post('packinglist/{id}/scan-part', [PackingListController::class, 'scanPart'])->name('packinglist.scan-part');
            Route::post('packinglist/{id}/add-to-box', [PackingListController::class, 'addToBox'])->name('packinglist.add-to-box');
            Route::post('packinglist/{id}/close-box', [PackingListController::class, 'closeBox'])->name('packinglist.close-box');
            Route::get('packinglist/{id}/current-box-items', [PackingListController::class, 'getCurrentBoxItems'])->name('packinglist.current-box-items');
            Route::post('packinglist/{id}/close-packing-list', [PackingListController::class, 'closePackingList'])->name('packinglist.close-packing-list');
        }
    );

    Route::get('/proposal/preview/{template}/{color}', [ProposalController::class, 'previewProposal'])->name('proposal.preview');
    Route::post('/proposal/template/setting', [ProposalController::class, 'saveProposalTemplateSettings'])->name('proposal.template.setting');

    Route::resource('goal', GoalController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    //Budget Planner //
    Route::resource('budget', BudgetController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::resource('account-assets', AssetController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::resource('custom-field', CustomFieldController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('custom-field/{customField}/export-options', [CustomFieldController::class, 'exportOptions'])->name('custom-field.export-options')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::post('chart-of-account/subtype', [ChartOfAccountController::class, 'getSubType'])->name('charofAccount.subType')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::resource('chart-of-account', ChartOfAccountController::class);
            Route::post('chart-of-account-new/{id}', [ChartOfAccountController::class, 'update'])->name('chart-of-account-new.update');
            Route::post('chart-of-account/import', [ChartOfAccountController::class, 'import'])->name('chart-of-account.import');
            Route::get('import/chart-of-account/file', [ChartOfAccountController::class, 'importFile'])->name('chart-of-account.file.import');
            Route::get('import/chart-of-account/chart_setup', [ChartOfAccountController::class, 'showChartSetup'])->name('chart-of-account.chart_setup');
            Route::post('/chart-of-accounts/setup', [ChartOfAccountController::class, 'submitOpeningBalances'])->name('accounts.chart.submit');
        }
    );

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::resource('chart-of-account-type', ChartOfAccountTypeController::class);
        }
    );

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::resource('chart-of-account-sub-type', ChartOfAccountSubTypeController::class);
        }
    );

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {

            Route::post('journal-entry/account/destroy', [JournalEntryController::class, 'accountDestroy'])->name('journal.account.destroy');

            Route::delete('journal-entry/journal/destroy/{item_id}', [JournalEntryController::class, 'journalDestroy'])->name('journal.destroy');
            Route::get('journal-entry/{journalEntry}/duplicate', [JournalEntryController::class, 'duplicate'])->name('journal-entry.duplicate');
            Route::post('journal-entry/{journalEntry}/reverse', [JournalEntryController::class, 'reverse'])->name('journal-entry.reverse');
            Route::get('journal-entry/{journalEntry_id}/ledger', [JournalEntryController::class, 'journal_ledger'])->name('journal-entry.ledger');
            Route::get('journal-entry/{id}/attachment/download', [JournalEntryController::class, 'downloadAttachment'])->name('journal-entry.attachment.download');
            Route::get('journal-entry/{id}/attachment/view', [JournalEntryController::class, 'viewAttachment'])->name('journal-entry.attachment.view');
            Route::resource('journal-entry', JournalEntryController::class);
        }
    );

    // Client Module

    Route::resource('clients', ClientController::class)->middleware(['auth', XSS::class]);

    Route::any('client-reset-password/{id}', [ClientController::class, 'clientPassword'])->name('clients.reset');
    Route::post('client-reset-password/{id}', [ClientController::class, 'clientPasswordReset'])->name('client.password.update');

    // Deal Module

    Route::post('/deals/user', [DealController::class, 'jsonUser'])->name('deal.user.json');
    Route::post('/deals/order', [DealController::class, 'order'])->name('deals.order')->middleware(['auth', XSS::class]);
    Route::post('/deals/change-pipeline', [DealController::class, 'changePipeline'])->name('deals.change.pipeline')->middleware(['auth', XSS::class]);
    Route::post('/leads/change-pipeline', [LeadController::class, 'changePipeline'])->name('leads.change.pipeline')->middleware(['auth', XSS::class]);
    Route::post('/deals/change-deal-status/{id}', [DealController::class, 'changeStatus'])->name('deals.change.status')->middleware(['auth', XSS::class]);
    Route::get('/deals/{id}/labels', [DealController::class, 'labels'])->name('deals.labels')->middleware(['auth', XSS::class]);
    Route::post('/deals/{id}/labels', [DealController::class, 'labelStore'])->name('deals.labels.store')->middleware(['auth', XSS::class]);
    Route::get('/deals/{id}/users', [DealController::class, 'userEdit'])->name('deals.users.edit')->middleware(['auth', XSS::class]);
    Route::put('/deals/{id}/users', [DealController::class, 'userUpdate'])->name('deals.users.update')->middleware(['auth', XSS::class]);
    Route::delete('/deals/{id}/users/{uid}', [DealController::class, 'userDestroy'])->name('deals.users.destroy')->middleware(['auth', XSS::class]);
    Route::get('/deals/{id}/clients', [DealController::class, 'clientEdit'])->name('deals.clients.edit')->middleware(['auth', XSS::class]);
    Route::put('/deals/{id}/clients', [DealController::class, 'clientUpdate'])->name('deals.clients.update')->middleware(['auth', XSS::class]);
    Route::delete('/deals/{id}/clients/{uid}', [DealController::class, 'clientDestroy'])->name('deals.clients.destroy')->middleware(['auth', XSS::class]);
    Route::get('/deals/{id}/products', [DealController::class, 'productEdit'])->name('deals.products.edit')->middleware(['auth', XSS::class]);
    Route::put('/deals/{id}/products', [DealController::class, 'productUpdate'])->name('deals.products.update')->middleware(['auth', XSS::class]);
    Route::delete('/deals/{id}/products/{uid}', [DealController::class, 'productDestroy'])->name('deals.products.destroy')->middleware(['auth', XSS::class]);
    Route::get('/deals/{id}/sources', [DealController::class, 'sourceEdit'])->name('deals.sources.edit')->middleware(['auth', XSS::class]);
    Route::put('/deals/{id}/sources', [DealController::class, 'sourceUpdate'])->name('deals.sources.update')->middleware(['auth', XSS::class]);
    Route::delete('/deals/{id}/sources/{uid}', [DealController::class, 'sourceDestroy'])->name('deals.sources.destroy')->middleware(['auth', XSS::class]);
    Route::post('/deals/{id}/file', [DealController::class, 'fileUpload'])->name('deals.file.upload')->middleware(['auth', XSS::class]);
    Route::get('/deals/{id}/file/{fid}', [DealController::class, 'fileDownload'])->name('deals.file.download')->middleware(['auth', XSS::class]);
    Route::delete('/deals/{id}/file/delete/{fid}', [DealController::class, 'fileDelete'])->name('deals.file.delete')->middleware(['auth', XSS::class]);
    Route::post('/deals/{id}/note', [DealController::class, 'noteStore'])->name('deals.note.store')->middleware(['auth']);
    Route::get('/deals/{id}/task', [DealController::class, 'taskCreate'])->name('deals.tasks.create')->middleware(['auth', XSS::class]);
    Route::post('/deals/{id}/task', [DealController::class, 'taskStore'])->name('deals.tasks.store')->middleware(['auth', XSS::class]);
    Route::get('/deals/{id}/task/{tid}/show', [DealController::class, 'taskShow'])->name('deals.tasks.show')->middleware(['auth', XSS::class]);
    Route::get('/deals/{id}/task/{tid}/edit', [DealController::class, 'taskEdit'])->name('deals.tasks.edit')->middleware(['auth', XSS::class]);
    Route::put('/deals/{id}/task/{tid}', [DealController::class, 'taskUpdate'])->name('deals.tasks.update')->middleware(['auth', XSS::class]);
    Route::put('/deals/{id}/task_status/{tid}', [DealController::class, 'taskUpdateStatus'])->name('deals.tasks.update_status')->middleware(['auth', XSS::class]);
    Route::delete('/deals/{id}/task/{tid}', [DealController::class, 'taskDestroy'])->name('deals.tasks.destroy')->middleware(['auth', XSS::class]);
    Route::get('/deals/{id}/discussions', [DealController::class, 'discussionCreate'])->name('deals.discussions.create')->middleware(['auth', XSS::class]);
    Route::post('/deals/{id}/discussions', [DealController::class, 'discussionStore'])->name('deals.discussion.store')->middleware(['auth', XSS::class]);
    Route::get('/deals/{id}/permission/{cid}', [DealController::class, 'permission'])->name('deals.client.permission')->middleware(['auth', XSS::class]);
    Route::put('/deals/{id}/permission/{cid}', [DealController::class, 'permissionStore'])->name('deals.client.permissions.store')->middleware(['auth', XSS::class]);
    Route::get('/deals/list', [DealController::class, 'deal_list'])->name('deals.list')->middleware(['auth', XSS::class]);
    Route::get('/deals/export', [DealController::class, 'export'])->name('deals.export')->middleware(['auth', XSS::class]);
    Route::post('import/deals', [DealController::class, 'import'])->name('deals.import');
    Route::get('import/deals/file', [DealController::class, 'importFile'])->name('deals.file.import');

    // Deal Calls

    Route::get('/deals/{id}/call', [DealController::class, 'callCreate'])->name('deals.calls.create')->middleware(['auth', XSS::class]);
    Route::post('/deals/{id}/call', [DealController::class, 'callStore'])->name('deals.calls.store')->middleware(['auth']);
    Route::get('/deals/{id}/call/{cid}/edit', [DealController::class, 'callEdit'])->name('deals.calls.edit')->middleware(['auth']);
    Route::put('/deals/{id}/call/{cid}', [DealController::class, 'callUpdate'])->name('deals.calls.update')->middleware(['auth']);
    Route::delete('/deals/{id}/call/{cid}', [DealController::class, 'callDestroy'])->name('deals.calls.destroy')->middleware(['auth', XSS::class]);

    // Deal Email

    Route::get('/deals/{id}/email', [DealController::class, 'emailCreate'])->name('deals.emails.create')->middleware(['auth', XSS::class]);
    Route::post('/deals/{id}/email', [DealController::class, 'emailStore'])->name('deals.emails.store')->middleware(['auth', XSS::class]);

    Route::resource('deals', DealController::class)->middleware(['auth', XSS::class]);
            // Deal Reminders
            Route::get('deal-reminders', [DealReminderController::class, 'index'])->name('deal-reminders.index')->middleware(['auth', XSS::class]);
            Route::get('deal-reminders/{id}/done', [DealReminderController::class, 'markDone'])->name('deal-reminders.done')->middleware(['auth', XSS::class]);

    // end Deal Module

    Route::get('/search', [UserController::class, 'search'])->name('search.json');
    Route::post('/stages/order', [StageController::class, 'order'])->name('stages.order');
    Route::post('/stages/json', [StageController::class, 'json'])->name('stages.json');

    Route::resource('stages', StageController::class);
    Route::resource('pipelines', PipelineController::class);
    Route::resource('labels', LabelController::class);
    Route::resource('sources', SourceController::class);
    Route::resource('payments', PaymentController::class);
    Route::resource('refunds', RefundController::class);
    Route::resource('customerpayments', CustomerPaymentController::class);
    Route::resource('customerrefunds', CustomerRefundController::class);
    Route::resource('custom_fields', CustomFieldController::class);

    // Leads Module

    Route::post('/lead_stages/order', [LeadStageController::class, 'order'])->name('lead_stages.order');

    Route::resource('lead_stages', LeadStageController::class)->middleware(['auth']);

    Route::post('/leads/json', [LeadController::class, 'json'])->name('leads.json');
    Route::post('/leads/order', [LeadController::class, 'order'])->name('leads.order')->middleware(['auth', XSS::class]);
    Route::get('/leads/list', [LeadController::class, 'lead_list'])->name('leads.list')->middleware(['auth', XSS::class]);
    Route::post('/leads/{id}/file', [LeadController::class, 'fileUpload'])->name('leads.file.upload')->middleware(['auth', XSS::class]);
    Route::get('/leads/{id}/file/{fid}', [LeadController::class, 'fileDownload'])->name('leads.file.download')->middleware(['auth', XSS::class]);
    Route::delete('/leads/{id}/file/delete/{fid}', [LeadController::class, 'fileDelete'])->name('leads.file.delete')->middleware(['auth', XSS::class]);
    Route::post('/leads/{id}/note', [LeadController::class, 'noteStore'])->name('leads.note.store')->middleware(['auth']);
    Route::get('/leads/{id}/labels', [LeadController::class, 'labels'])->name('leads.labels')->middleware(['auth', XSS::class]);
    Route::post('/leads/{id}/labels', [LeadController::class, 'labelStore'])->name('leads.labels.store')->middleware(['auth', XSS::class]);
    Route::get('/leads/{id}/users', [LeadController::class, 'userEdit'])->name('leads.users.edit')->middleware(['auth', XSS::class]);
    Route::put('/leads/{id}/users', [LeadController::class, 'userUpdate'])->name('leads.users.update')->middleware(['auth', XSS::class]);
    Route::delete('/leads/{id}/users/{uid}', [LeadController::class, 'userDestroy'])->name('leads.users.destroy')->middleware(['auth', XSS::class]);
    Route::get('/leads/{id}/products', [LeadController::class, 'productEdit'])->name('leads.products.edit')->middleware(['auth', XSS::class]);
    Route::put('/leads/{id}/products', [LeadController::class, 'productUpdate'])->name('leads.products.update')->middleware(['auth', XSS::class]);
    Route::delete('/leads/{id}/products/{uid}', [LeadController::class, 'productDestroy'])->name('leads.products.destroy')->middleware(['auth', XSS::class]);
    Route::get('/leads/{id}/sources', [LeadController::class, 'sourceEdit'])->name('leads.sources.edit')->middleware(['auth', XSS::class]);
    Route::put('/leads/{id}/sources', [LeadController::class, 'sourceUpdate'])->name('leads.sources.update')->middleware(['auth', XSS::class]);
    Route::delete('/leads/{id}/sources/{uid}', [LeadController::class, 'sourceDestroy'])->name('leads.sources.destroy')->middleware(['auth', XSS::class]);
    Route::get('/leads/{id}/discussions', [LeadController::class, 'discussionCreate'])->name('leads.discussions.create')->middleware(['auth', XSS::class]);
    Route::post('/leads/{id}/discussions', [LeadController::class, 'discussionStore'])->name('leads.discussion.store')->middleware(['auth', XSS::class]);
    Route::get('/leads/{id}/show_convert', [LeadController::class, 'showConvertToDeal'])->name('leads.convert.deal')->middleware(['auth', XSS::class]);
    Route::post('/leads/{id}/convert', [LeadController::class, 'convertToDeal'])->name('leads.convert.to.deal')->middleware(['auth', XSS::class]);
    Route::get('/leads/export', [LeadController::class, 'export'])->name('leads.export')->middleware(['auth', XSS::class]);
    Route::post('import/leads', [LeadController::class, 'import'])->name('leads.import');
    Route::get('import/leads/file', [LeadController::class, 'importFile'])->name('leads.file.import');



    // Lead Calls
    Route::get('/leads/{id}/call', [LeadController::class, 'callCreate'])->name('leads.calls.create')->middleware(['auth', XSS::class]);
    Route::post('/leads/{id}/call', [LeadController::class, 'callStore'])->name('leads.calls.store')->middleware(['auth']);
    Route::get('/leads/{id}/call/{cid}/edit', [LeadController::class, 'callEdit'])->name('leads.calls.edit')->middleware(['auth', XSS::class]);
    Route::put('/leads/{id}/call/{cid}', [LeadController::class, 'callUpdate'])->name('leads.calls.update')->middleware(['auth']);
    Route::delete('/leads/{id}/call/{cid}', [LeadController::class, 'callDestroy'])->name('leads.calls.destroy')->middleware(['auth', XSS::class]);

    // Lead Email

    Route::get('/leads/{id}/email', [LeadController::class, 'emailCreate'])->name('leads.emails.create')->middleware(['auth', XSS::class]);
    Route::post('/leads/{id}/email', [LeadController::class, 'emailStore'])->name('leads.emails.store')->middleware(['auth']);

    Route::resource('leads', LeadController::class)->middleware(['auth', XSS::class]);

    // end Leads Module

    Route::get('user/{id}/plan', [UserController::class, 'upgradePlan'])->name('plan.upgrade')->middleware(['auth', XSS::class]);
    Route::get('user/{id}/plan/{pid}', [UserController::class, 'activePlan'])->name('plan.active')->middleware(['auth', XSS::class]);
    Route::get('/{uid}/notification/seen', [UserController::class, 'notificationSeen'])->name('notification.seen');

    // Email Templates
    Route::get('email_template_lang/{id}/{lang?}', [EmailTemplateController::class, 'manageEmailLang'])->name('manage.email.language')->middleware(['auth', XSS::class]);
    Route::any('email_template_store', [EmailTemplateController::class, 'updateStatus'])->name('status.email.language')->middleware(['auth']);
    Route::any('email_template_store/{pid}', [EmailTemplateController::class, 'storeEmailLang'])->name('store.email.language')->middleware(['auth']);
    Route::resource('email_template', EmailTemplateController::class)->middleware(['auth', XSS::class]);
    // End Email Templates

    // HRM
    Route::resource('user', UserController::class)->middleware(['auth', XSS::class]);
    Route::post('employee/json', [EmployeeController::class, 'json'])->name('employee.json')->middleware(['auth', XSS::class]);
    Route::post('branch/employee/json', [EmployeeController::class, 'employeeJson'])->name('branch.employee.json')->middleware(['auth', XSS::class]);
    Route::get('employee-profile', [EmployeeController::class, 'profile'])->name('employee.profile')->middleware(['auth', XSS::class]);
    Route::get('show-employee-profile/{id}', [EmployeeController::class, 'profileShow'])->name('show.employee.profile')->middleware(['auth', XSS::class]);

    Route::get('lastlogin', [EmployeeController::class, 'lastLogin'])->name('lastlogin')->middleware(['auth', XSS::class]);

    Route::resource('employee', EmployeeController::class)->middleware(['auth', XSS::class]);
    Route::resource('employeepayment', EmployeePaymentController::class)->middleware(['auth', XSS::class]);
    Route::get('task-manager', [TaskManagerController::class, 'index'])->name('task.manager.index')->middleware(['auth', XSS::class]);
    Route::resource('task-master', TaskMasterController::class)->middleware(['auth', XSS::class]);

    Route::get('daily-tasks', [EmployeeDailyTaskController::class, 'index'])->name('daily-tasks.index')->middleware(['auth', XSS::class]);
    Route::get('daily-tasks/create', [EmployeeDailyTaskController::class, 'create'])->name('daily-tasks.create')->middleware(['auth', XSS::class]);
    Route::get('daily-tasks/report', [EmployeeDailyTaskController::class, 'report'])->name('daily-tasks.report')->middleware(['auth', XSS::class]);
    Route::get('daily-tasks/chart', [EmployeeDailyTaskController::class, 'chart'])->name('daily-tasks.chart')->middleware(['auth', XSS::class]);
    Route::get('daily-tasks/task-masters', [EmployeeDailyTaskController::class, 'taskMastersForEmployee'])->name('daily-tasks.task-masters')->middleware(['auth', XSS::class]);
    Route::post('daily-tasks', [EmployeeDailyTaskController::class, 'store'])->name('daily-tasks.store')->middleware(['auth', XSS::class]);
    Route::get('daily-tasks/{employee_daily_log}', [EmployeeDailyTaskController::class, 'show'])->name('daily-tasks.show')->middleware(['auth', XSS::class]);
    Route::get('daily-tasks/{employee_daily_log}/edit', [EmployeeDailyTaskController::class, 'edit'])->name('daily-tasks.edit')->middleware(['auth', XSS::class]);
    Route::put('daily-tasks/{employee_daily_log}', [EmployeeDailyTaskController::class, 'update'])->name('daily-tasks.update')->middleware(['auth', XSS::class]);
    Route::delete('daily-tasks/{employee_daily_log}', [EmployeeDailyTaskController::class, 'destroy'])->name('daily-tasks.destroy')->middleware(['auth', XSS::class]);

    Route::post('employee/getdepartment', [EmployeeController::class, 'getDepartment'])->name('employee.getdepartment')->middleware(['auth', XSS::class]);

    Route::resource('department', DepartmentController::class)->middleware(['auth', XSS::class]);
    Route::resource('designation', DesignationController::class)->middleware(['auth', XSS::class]);
    Route::resource('document', DocumentController::class)->middleware(['auth', XSS::class]);
    Route::resource('branch', BranchController::class)->middleware(['auth', XSS::class]);

    // Hrm EmployeeController

    Route::get('employee/salary/{eid}', [SetSalaryController::class, 'employeeBasicSalary'])->name('employee.basic.salary')->middleware(['auth', XSS::class]);

    //payslip

    Route::resource('paysliptype', PayslipTypeController::class)->middleware(['auth', XSS::class]);
    Route::resource('allowance', AllowanceController::class)->middleware(['auth', XSS::class]);
    Route::resource('commission', CommissionController::class)->middleware(['auth', XSS::class]);
    Route::resource('allowanceoption', AllowanceOptionController::class)->middleware(['auth', XSS::class]);
    Route::resource('loanoption', LoanOptionController::class)->middleware(['auth', XSS::class]);
    Route::resource('deductionoption', DeductionOptionController::class)->middleware(['auth', XSS::class]);
    Route::resource('loan', LoanController::class)->middleware(['auth', XSS::class]);
    Route::resource('saturationdeduction', SaturationDeductionController::class)->middleware(['auth', XSS::class]);
    Route::resource('otherpayment', OtherPaymentController::class)->middleware(['auth', XSS::class]);
    Route::resource('overtime', OvertimeController::class)->middleware(['auth', XSS::class]);

    Route::get('employee/salary/{eid}', [SetSalaryController::class, 'employeeBasicSalary'])->name('employee.basic.salary')->middleware(['auth', XSS::class]);
    Route::post('employee/update/sallary/{id}', [SetSalaryController::class, 'employeeUpdateSalary'])->name('employee.salary.update')->middleware(['auth', XSS::class]);
    Route::get('salary/employeeSalary', [SetSalaryController::class, 'employeeSalary'])->name('employeesalary')->middleware(['auth', XSS::class]);
    Route::resource('setsalary', SetSalaryController::class)->middleware(['auth', XSS::class]);

    Route::get('allowances/create/{eid}', [AllowanceController::class, 'allowanceCreate'])->name('allowances.create')->middleware(['auth', XSS::class]);
    Route::get('commissions/create/{eid}', [CommissionController::class, 'commissionCreate'])->name('commissions.create')->middleware(['auth', XSS::class]);
    Route::get('loans/create/{eid}', [LoanController::class, 'loanCreate'])->name('loans.create')->middleware(['auth', XSS::class]);
    Route::get('saturationdeductions/create/{eid}', [SaturationDeductionController::class, 'saturationdeductionCreate'])->name('saturationdeductions.create')->middleware(['auth', XSS::class]);
    Route::get('otherpayments/create/{eid}', [OtherPaymentController::class, 'otherpaymentCreate'])->name('otherpayments.create')->middleware(['auth', XSS::class]);
    Route::get('overtimes/create/{eid}', [OvertimeController::class, 'overtimeCreate'])->name('overtimes.create')->middleware(['auth', XSS::class]);
    Route::get('payslip/paysalary/{id}/{date}', [PaySlipController::class, 'paysalary'])->name('payslip.paysalary')->middleware(['auth', XSS::class]);
    Route::get('payslip/bulk_pay_create/{date}', [PaySlipController::class, 'bulk_pay_create'])->name('payslip.bulk_pay_create')->middleware(['auth', XSS::class]);
    Route::post('payslip/bulkpayment/{date}', [PaySlipController::class, 'bulkpayment'])->name('payslip.bulkpayment')->middleware(['auth', XSS::class]);
    Route::post('payslip/search_json', [PaySlipController::class, 'search_json'])->name('payslip.search_json')->middleware(['auth', XSS::class]);
    Route::get('payslip/employeepayslip', [PaySlipController::class, 'employeepayslip'])->name('payslip.employeepayslip')->middleware(['auth', XSS::class]);
    Route::get('payslip/showemployee/{id}', [PaySlipController::class, 'showemployee'])->name('payslip.showemployee')->middleware(['auth', XSS::class]);
    Route::get('payslip/editemployee/{id}', [PaySlipController::class, 'editemployee'])->name('payslip.editemployee')->middleware(['auth', XSS::class]);
    Route::post('payslip/editemployee/{id}', [PaySlipController::class, 'updateEmployee'])->name('payslip.updateemployee')->middleware(['auth', XSS::class]);
    Route::get('payslip/pdf/{id}/{m}', [PaySlipController::class, 'pdf'])->name('payslip.pdf')->middleware(['auth', XSS::class]);
    Route::get('payslip/payslipPdf/{id}', [PaySlipController::class, 'payslipPdf'])->name('payslip.payslipPdf')->middleware(['auth', XSS::class]);
    Route::get('payslip/send/{id}/{m}', [PaySlipController::class, 'send'])->name('payslip.send')->middleware(['auth', XSS::class]);
    Route::get('payslip/delete/{id}', [PaySlipController::class, 'destroy'])->name('payslip.delete')->middleware(['auth', XSS::class]);
    Route::resource('payslip', PaySlipController::class)->middleware(['auth', XSS::class]);

    Route::resource('company-policy', CompanyPolicyController::class)->middleware(['auth', XSS::class]);
    Route::resource('indicator', IndicatorController::class)->middleware(['auth', XSS::class]);
    Route::resource('appraisal', AppraisalController::class)->middleware(['auth', XSS::class]);

    Route::post('branch/employee/json', [EmployeeController::class, 'employeeJson'])->name('branch.employee.json')->middleware(['auth', XSS::class]);

    Route::resource('goaltype', GoalTypeController::class)->middleware(['auth', XSS::class]);
    Route::resource('goaltracking', GoalTrackingController::class)->middleware(['auth', XSS::class]);
    Route::resource('account-assets', AssetController::class)->middleware(['auth', XSS::class]);

    Route::post('event/getdepartment', [EventController::class, 'getdepartment'])->name('event.getdepartment')->middleware(['auth', XSS::class]);
    Route::post('event/getemployee', [EventController::class, 'getemployee'])->name('event.getemployee')->middleware(['auth', XSS::class]);

    Route::resource('event', EventController::class)->middleware(['auth', XSS::class]);

    Route::post('meeting/getdepartment', [MeetingController::class, 'getdepartment'])->name('meeting.getdepartment')->middleware(['auth', XSS::class]);
    Route::post('meeting/getemployee', [MeetingController::class, 'getemployee'])->name('meeting.getemployee')->middleware(['auth', XSS::class]);

    Route::resource('meeting', MeetingController::class)->middleware(['auth', XSS::class]);
    Route::resource('trainingtype', TrainingTypeController::class)->middleware(['auth', XSS::class]);
    Route::resource('trainer', TrainerController::class)->middleware(['auth', XSS::class]);

    Route::post('training/status', [TrainingController::class, 'updateStatus'])->name('training.status')->middleware(['auth', XSS::class]);

    Route::resource('training', TrainingController::class)->middleware(['auth', XSS::class]);

    // HRM - HR Module

    Route::resource('awardtype', AwardTypeController::class)->middleware(['auth', XSS::class]);
    Route::resource('award', AwardController::class)->middleware(['auth', XSS::class]);
    Route::resource('resignation', ResignationController::class)->middleware(['auth', XSS::class]);
    Route::resource('travel', TravelController::class)->middleware(['auth', XSS::class]);
    Route::resource('promotion', PromotionController::class)->middleware(['auth', XSS::class]);
    Route::resource('complaint', ComplaintController::class)->middleware(['auth', XSS::class]);
    Route::resource('warning', WarningController::class)->middleware(['auth', XSS::class]);

    Route::resource('termination', TerminationController::class)->middleware(['auth', XSS::class]);
    Route::get('termination/{id}/description', [TerminationController::class, 'description'])->name('termination.description');
    Route::resource('terminationtype', TerminationTypeController::class)->middleware(['auth', XSS::class]);

    Route::post('announcement/getdepartment', [AnnouncementController::class, 'getdepartment'])->name('announcement.getdepartment');
    Route::post('announcement/getemployee', [AnnouncementController::class, 'getemployee'])->name('announcement.getemployee');
    Route::resource('announcement', AnnouncementController::class)->middleware(['auth', XSS::class]);

    Route::resource('holiday', HolidayController::class)->middleware(['auth', XSS::class]);
    Route::get('holiday-calender', [HolidayController::class, 'calender'])->name('holiday.calender');

    // Recruitement

    Route::resource('job-category', JobCategoryController::class)->middleware(['auth', XSS::class]);

    Route::resource('job-stage', JobStageController::class)->middleware(['auth', XSS::class]);
    Route::post('job-stage/order', [JobStageController::class, 'order'])->name('job.stage.order');

    Route::resource('job', JobController::class)->middleware(['auth', XSS::class]);

    Route::get('candidates-job-applications', [JobApplicationController::class, 'candidate'])->name('job.application.candidate')->middleware(['auth', XSS::class]);

    Route::resource('job-application', JobApplicationController::class)->middleware(['auth', XSS::class]);
    Route::post('job-application/order', [JobApplicationController::class, 'order'])->name('job.application.order')->middleware([XSS::class]);
    Route::post('job-application/{id}/rating', [JobApplicationController::class, 'rating'])->name('job.application.rating')->middleware([XSS::class]);
    Route::delete('job-application/{id}/archive', [JobApplicationController::class, 'archive'])->name('job.application.archive')->middleware(['auth', XSS::class]);
    Route::post('job-application/{id}/skill/store', [JobApplicationController::class, 'addSkill'])->name('job.application.skill.store')->middleware(['auth', XSS::class]);
    Route::post('job-application/{id}/note/store', [JobApplicationController::class, 'addNote'])->name('job.application.note.store')->middleware(['auth', XSS::class]);
    Route::delete('job-application/{id}/note/destroy', [JobApplicationController::class, 'destroyNote'])->name('job.application.note.destroy')->middleware(['auth', XSS::class]);
    Route::post('job-application/getByJob', [JobApplicationController::class, 'getByJob'])->name('get.job.application')->middleware(['auth', XSS::class]);
    Route::get('job-onboard', [JobApplicationController::class, 'jobOnBoard'])->name('job.on.board')->middleware(['auth', XSS::class]);
    Route::get('job-onboard/create/{id}', [JobApplicationController::class, 'jobBoardCreate'])->name('job.on.board.create')->middleware(['auth', XSS::class]);
    Route::post('job-onboard/store/{id}', [JobApplicationController::class, 'jobBoardStore'])->name('job.on.board.store')->middleware(['auth', XSS::class]);
    Route::get('job-onboard/edit/{id}', [JobApplicationController::class, 'jobBoardEdit'])->name('job.on.board.edit')->middleware(['auth', XSS::class]);
    Route::post('job-onboard/update/{id}', [JobApplicationController::class, 'jobBoardUpdate'])->name('job.on.board.update')->middleware(['auth', XSS::class]);
    Route::delete('job-onboard/delete/{id}', [JobApplicationController::class, 'jobBoardDelete'])->name('job.on.board.delete')->middleware(['auth', XSS::class]);
    Route::get('job-onboard/convert/{id}', [JobApplicationController::class, 'jobBoardConvert'])->name('job.on.board.convert')->middleware(['auth', XSS::class]);
    Route::post('job-onboard/convert/{id}', [JobApplicationController::class, 'jobBoardConvertData'])->name('job.on.board.convert')->middleware(['auth', XSS::class]);
    Route::post('job-application/stage/change', [JobApplicationController::class, 'stageChange'])->name('job.application.stage.change')->middleware(['auth', XSS::class]);

    Route::resource('custom-question', CustomQuestionController::class)->middleware(['auth', XSS::class]);
    Route::resource('interview-schedule', InterviewScheduleController::class)->middleware(['auth', XSS::class]);
    Route::get('interview-schedule/create/{id?}', [InterviewScheduleController::class, 'create'])->name('interview-schedule.create')->middleware(['auth', XSS::class]);
    Route::get('taskboard/{view?}', [ProjectTaskController::class, 'taskBoard'])->name('taskBoard.view')->middleware(['auth', XSS::class]);
    Route::get('taskboard-view', [ProjectTaskController::class, 'taskboardView'])->name('project.taskboard.view')->middleware(['auth', XSS::class]);

    Route::resource('document-upload', DucumentUploadController::class)->middleware(['auth', XSS::class]);
    Route::resource('transfer', TransferController::class)->middleware(['auth', XSS::class]);
    Route::get('attendanceemployee/bulkattendance', [AttendanceEmployeeController::class, 'bulkAttendance'])->name('attendanceemployee.bulkattendance')->middleware(['auth', XSS::class]);
    Route::post('attendanceemployee/bulkattendance', [AttendanceEmployeeController::class, 'bulkAttendanceData'])->name('attendanceemployee.bulkattendance')->middleware(['auth', XSS::class]);
    Route::post('attendanceemployee/attendance', [AttendanceEmployeeController::class, 'attendance'])->name('attendanceemployee.attendance')->middleware(['auth', XSS::class]);
    Route::patch('attendanceemployee/{attendance}/note', [AttendanceEmployeeController::class, 'updateNote'])->name('attendanceemployee.note.update')->middleware(['auth', XSS::class]);

    Route::resource('attendanceemployee', AttendanceEmployeeController::class)->middleware(['auth', XSS::class]);
    Route::resource('leavetype', LeaveTypeController::class)->middleware(['auth', XSS::class]);
    Route::get('report/leave', [ReportController::class, 'leave'])->name('report.leave')->middleware(['auth', XSS::class]);
    Route::get('employee/{id}/leave/{status}/{type}/{month}/{year}', [ReportController::class, 'employeeLeave'])->name('report.employee.leave')->middleware(['auth', XSS::class]);
    Route::get('leave/{id}/action', [LeaveController::class, 'action'])->name('leave.action')->middleware(['auth', XSS::class]);
    Route::post('leave/changeaction', [LeaveController::class, 'changeaction'])->name('leave.changeaction')->middleware(['auth', XSS::class]);
    Route::post('leave/jsoncount', [LeaveController::class, 'jsoncount'])->name('leave.jsoncount')->middleware(['auth', XSS::class]);

    Route::resource('leave', LeaveController::class)->middleware(['auth', XSS::class]);

    Route::resource('earlyleave', DailyLeaveController::class)->middleware(['auth', XSS::class]);
    Route::post('earlyleave/changeaction', [DailyLeaveController::class, 'changeaction'])->name('earlyleave.changeaction')->middleware(['auth', XSS::class]);
    Route::get('earlyleave/{id}/action', [DailyLeaveController::class, 'action'])->name('earlyleave.action')->middleware(['auth', XSS::class]);
    Route::post('earlyleave/jsoncount', [DailyLeaveController::class, 'jsoncount'])->name('leave.jsoncount')->middleware(['auth', XSS::class]);

    Route::get('reports-leave', [ReportController::class, 'leave'])->name('report.leave')->middleware(['auth', XSS::class]);
    Route::get('employee/{id}/leave/{status}/{type}/{month}/{year}', [ReportController::class, 'employeeLeave'])->name('report.employee.leave')->middleware(['auth', XSS::class]);

    Route::get('reports-payroll', [ReportController::class, 'payroll'])->name('report.payroll')->middleware(['auth', XSS::class]);
    Route::post('reports-payroll/getdepartment', [ReportController::class, 'getPayrollDepartment'])->name('report.payroll.getdepartment')->middleware(['auth', XSS::class]);
    Route::post('reports-payroll/getemployee', [ReportController::class, 'getPayrollEmployee'])->name('report.payroll.getemployee')->middleware(['auth', XSS::class]);

    Route::get('reports-monthly-attendance', [ReportController::class, 'monthlyAttendance'])->name('report.monthly.attendance')->middleware(['auth', XSS::class]);
    Route::get('report/attendance/{month}/{branch}/{department}', [ReportController::class, 'exportCsv'])->name('report.attendance')->middleware(['auth', XSS::class]);

    //crm report
    Route::get('reports-lead', [ReportController::class, 'leadReport'])->name('report.lead')->middleware(['auth', XSS::class]);
    Route::get('reports-deal', [ReportController::class, 'dealReport'])->name('report.deal')->middleware(['auth', XSS::class]);

    //pos report
    Route::get('reports-warehouse', [ReportController::class, 'warehouseReport'])->name('report.warehouse')->middleware(['auth', XSS::class]);

    Route::get('reports-daily-purchase', [ReportController::class, 'purchaseDailyReport'])->name('report.daily.purchase')->middleware(['auth', XSS::class]);
    Route::get('reports-monthly-purchase', [ReportController::class, 'purchaseMonthlyReport'])->name('report.monthly.purchase')->middleware(['auth', XSS::class]);

    Route::get('reports-daily-pos', [ReportController::class, 'posDailyReport'])->name('report.daily.pos')->middleware(['auth', XSS::class]);
    Route::get('reports-monthly-pos', [ReportController::class, 'posMonthlyReport'])->name('report.monthly.pos')->middleware(['auth', XSS::class]);

    Route::get('reports-pos-vs-purchase', [ReportController::class, 'posVsPurchaseReport'])->name('report.pos.vs.purchase')->middleware(['auth', XSS::class]);

    // User Module

    Route::get('users/{view?}', [UserController::class, 'index'])->name('users')->middleware(['auth', XSS::class]);
    Route::get('users-view', [UserController::class, 'filterUserView'])->name('filter.user.view')->middleware(['auth', XSS::class]);
    Route::get('checkuserexists', [UserController::class, 'checkUserExists'])->name('user.exists')->middleware(['auth', XSS::class]);
    Route::get('profile', [UserController::class, 'profile'])->name('profile')->middleware(['auth', XSS::class]);
    Route::post('/profile', [UserController::class, 'updateProfile'])->name('update.profile')->middleware(['auth', XSS::class]);
    Route::get('user/info/{id}', [UserController::class, 'userInfo'])->name('users.info')->middleware(['auth', XSS::class]);
    Route::get('user/{id}/info/{type}', [UserController::class, 'getProjectTask'])->name('user.info.popup')->middleware(['auth', XSS::class]);
    Route::delete('users/{id}', [UserController::class, 'destroy'])->name('user.destroy')->middleware(['auth', XSS::class]);
    // End User Module

    // Search
    Route::get('/search', [UserController::class, 'search'])->name('search.json');
    // end

    // Milestone Module

    Route::get('projects/{id}/milestone', [ProjectController::class, 'milestone'])->name('project.milestone')->middleware(['auth', XSS::class]);

    //Route::delete(
    //    '/projects/{id}/users/{uid}', [
    //                                    'as' => 'projects.users.destroy',
    //                                    'uses' => 'ProjectController@userDestroy',
    //                                ]
    //)->middleware(
    //    [
    //        'auth',
    //        XSS::class,
    //    ]
    //);
    Route::post('projects/{id}/milestone', [ProjectController::class, 'milestoneStore'])->name('project.milestone.store')->middleware(['auth', XSS::class]);
    Route::get('projects/milestone/{id}/edit', [ProjectController::class, 'milestoneEdit'])->name('project.milestone.edit')->middleware(['auth', XSS::class]);
    Route::post('projects/milestone/{id}', [ProjectController::class, 'milestoneUpdate'])->name('project.milestone.update')->middleware(['auth', XSS::class]);
    Route::delete('projects/milestone/{id}', [ProjectController::class, 'milestoneDestroy'])->name('project.milestone.destroy')->middleware(['auth', XSS::class]);
    Route::get('projects/milestone/{id}/show', [ProjectController::class, 'milestoneShow'])->name('project.milestone.show')->middleware(['auth', XSS::class]);

    // End Milestone

    // Project Module

    Route::get('invite-project-member/{id}', [ProjectController::class, 'inviteMemberView'])->name('invite.project.member.view')->middleware(['auth', XSS::class]);
    Route::post('invite-project-user-member', [ProjectController::class, 'inviteProjectUserMember'])->name('invite.project.user.member')->middleware(['auth', XSS::class]);

    Route::delete('projects/{id}/users/{uid}', [ProjectController::class, 'destroyProjectUser'])->name('projects.user.destroy')->middleware(['auth', XSS::class]);
    Route::get('project/{view?}', [ProjectController::class, 'index'])->name('projects.list')->middleware(['auth', XSS::class]);
    Route::get('projects-view', [ProjectController::class, 'filterProjectView'])->name('filter.project.view')->middleware(['auth', XSS::class]);
    Route::post('projects/{id}/store-stages/{slug}', [ProjectController::class, 'storeProjectTaskStages'])->name('project.stages.store')->middleware(['auth', XSS::class]);

    Route::patch('remove-user-from-project/{project_id}/{user_id}', [ProjectController::class, 'removeUserFromProject'])->name('remove.user.from.project')->middleware(['auth', XSS::class]);
    Route::get('projects-users', [ProjectController::class, 'loadUser'])->name('project.user')->middleware(['auth', XSS::class]);
    Route::get('projects/{id}/gantt/{duration?}', [ProjectController::class, 'gantt'])->name('projects.gantt')->middleware(['auth', XSS::class]);
    Route::post('projects/{id}/gantt', [ProjectController::class, 'ganttPost'])->name('projects.gantt.post')->middleware(['auth', XSS::class]);

    Route::resource('projects', ProjectController::class)->middleware(['auth', XSS::class]);

    // User Permission
    Route::get('projects/{id}/user/{uid}/permission', [ProjectController::class, 'userPermission'])->name('projects.user.permission')->middleware(['auth', XSS::class]);
    Route::post('projects/{id}/user/{uid}/permission', [ProjectController::class, 'userPermissionStore'])->name('projects.user.permission.store')->middleware(['auth', XSS::class]);

    // End Project Module

    // Task Module

    Route::get('stage/{id}/tasks', [ProjectTaskController::class, 'getStageTasks'])->name('stage.tasks')->middleware(['auth', XSS::class]);

    // Project Task Module

    Route::get('/projects/{id}/task', [ProjectTaskController::class, 'index'])->name('projects.tasks.index')->middleware(['auth', XSS::class]);
    Route::get('/projects/{pid}/task/{sid}', [ProjectTaskController::class, 'create'])->name('projects.tasks.create')->middleware(['auth', XSS::class]);
    Route::post('/projects/{pid}/task/{sid}', [ProjectTaskController::class, 'store'])->name('projects.tasks.store')->middleware(['auth', XSS::class]);
    Route::get('/projects/{id}/task/{tid}/show', [ProjectTaskController::class, 'show'])->name('projects.tasks.show')->middleware(['auth', XSS::class]);
    Route::get('/projects/{id}/task/{tid}/edit', [ProjectTaskController::class, 'edit'])->name('projects.tasks.edit')->middleware(['auth', XSS::class]);
    Route::post('/projects/{id}/task/update/{tid}', [ProjectTaskController::class, 'update'])->name('projects.tasks.update')->middleware(['auth', XSS::class]);
    Route::delete('/projects/{id}/task/{tid}', [ProjectTaskController::class, 'destroy'])->name('projects.tasks.destroy')->middleware(['auth', XSS::class]);
    Route::patch('/projects/{id}/task/order', [ProjectTaskController::class, 'taskOrderUpdate'])->name('tasks.update.order')->middleware(['auth', XSS::class]);
    Route::patch('update-task-priority-color', [ProjectTaskController::class, 'updateTaskPriorityColor'])->name('update.task.priority.color')->middleware(['auth', XSS::class]);

    Route::post('/projects/{id}/comment/{tid}/file', [ProjectTaskController::class, 'commentStoreFile'])->name('comment.store.file')->middleware(['auth', XSS::class]);
    Route::delete('/projects/{id}/comment/{tid}/file/{fid}', [ProjectTaskController::class, 'commentDestroyFile'])->name('comment.destroy.file');
    Route::post('/projects/{id}/comment/{tid}', [ProjectTaskController::class, 'commentStore'])->name('task.comment.store');
    Route::delete('/projects/{id}/comment/{tid}/{cid}', [ProjectTaskController::class, 'commentDestroy'])->name('comment.destroy');
    Route::post('/projects/{id}/checklist/{tid}', [ProjectTaskController::class, 'checklistStore'])->name('checklist.store');
    Route::post('/projects/{id}/checklist/update/{cid}', [ProjectTaskController::class, 'checklistUpdate'])->name('checklist.update');
    Route::delete('/projects/{id}/checklist/{cid}', [ProjectTaskController::class, 'checklistDestroy'])->name('checklist.destroy');
    Route::post('/projects/{id}/change/{tid}/fav', [ProjectTaskController::class, 'changeFav'])->name('change.fav');
    Route::post('/projects/{id}/change/{tid}/complete', [ProjectTaskController::class, 'changeCom'])->name('change.complete');
    Route::post('/projects/{id}/change/{tid}/progress', [ProjectTaskController::class, 'changeProg'])->name('change.progress');
    Route::get('/projects/task/{id}/get', [ProjectTaskController::class, 'taskGet'])->name('projects.tasks.get')->middleware(['auth', XSS::class]);
    Route::get('/calendar/{id}/show', [ProjectTaskController::class, 'calendarShow'])->name('task.calendar.show')->middleware(['auth', XSS::class]);
    Route::post('/calendar/{id}/drag', [ProjectTaskController::class, 'calendarDrag'])->name('task.calendar.drag');
    Route::get('calendar/{task}/{pid?}', [ProjectTaskController::class, 'calendarView'])->name('task.calendar')->middleware(['auth', XSS::class]);

    Route::resource('project-task-stages', TaskStageController::class)->middleware(['auth', XSS::class]);
    Route::post('/project-task-stages/order', [TaskStageController::class, 'order'])->name('project-task-stages.order');

    Route::post('project-task-new-stage', [TaskStageController::class, 'storingValue'])->name('new-task-stage')->middleware(['auth', XSS::class]);
    // End Task Module

    // Project Expense Module
    Route::get('/projects/{id}/expense', [ExpenseController::class, 'index'])->name('projects.expenses.index')->middleware(['auth', XSS::class]);
    Route::get('/projects/{pid}/expense/create', [ExpenseController::class, 'create'])->name('projects.expenses.create')->middleware(['auth', XSS::class]);
    Route::post('/projects/{pid}/expense/store', [ExpenseController::class, 'store'])->name('projects.expenses.store')->middleware(['auth', XSS::class]);
    Route::get('/projects/{id}/expense/{eid}/edit', [ExpenseController::class, 'edit'])->name('projects.expenses.edit')->middleware(['auth', XSS::class]);
    Route::post('/projects/{id}/expense/{eid}', [ExpenseController::class, 'update'])->name('projects.expenses.update')->middleware(['auth', XSS::class]);
    Route::delete('/projects/{eid}/expense/', [ExpenseController::class, 'destroy'])->name('projects.expenses.destroy')->middleware(['auth', XSS::class]);
    Route::get('/expense-list', [ExpenseController::class, 'expenseList'])->name('expense.list')->middleware(['auth', XSS::class]);

    // contract type
    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::resource('contractType', ContractTypeController::class);
        }
    );

    // Project Timesheet
    Route::get('append-timesheet-task-html', [TimesheetController::class, 'appendTimesheetTaskHTML'])->name('append.timesheet.task.html')->middleware(['auth', XSS::class]);
    //    Route::get('timesheet-table-view', [TimesheetController::class, 'filterTimesheetTableView'])->name('filter.timesheet.table.view')->middleware(['auth', XSS::class]);
    Route::get('timesheet-view', [TimesheetController::class, 'filterTimesheetView'])->name('filter.timesheet.view')->middleware(['auth', XSS::class]);
    Route::get('timesheet-list', [TimesheetController::class, 'timesheetList'])->name('timesheet.list')->middleware(['auth', XSS::class]);
    Route::get('timesheet-list-get', [TimesheetController::class, 'timesheetListGet'])->name('timesheet.list.get')->middleware(['auth', XSS::class]);
    Route::get('/project/{id}/timesheet', [TimesheetController::class, 'timesheetView'])->name('timesheet.index')->middleware(['auth', XSS::class]);
    Route::get('/project/{id}/timesheet/create', [TimesheetController::class, 'timesheetCreate'])->name('timesheet.create')->middleware(['auth', XSS::class]);
    Route::post('/project/timesheet', [TimesheetController::class, 'timesheetStore'])->name('timesheet.store')->middleware(['auth', XSS::class]);
    Route::get('/project/timesheet/{project_id}/edit/{timesheet_id}', [TimesheetController::class, 'timesheetEdit'])->name('timesheet.edit')->middleware(['auth', XSS::class]);
    Route::any('/project/timesheet/update/{timesheet_id}', [TimesheetController::class, 'timesheetUpdate'])->name('timesheet.update')->middleware(['auth', XSS::class]);

    Route::delete('/project/timesheet/{timesheet_id}', [TimesheetController::class, 'timesheetDestroy'])->name('timesheet.destroy')->middleware(['auth', XSS::class]);

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
            ],
        ],
        function () {
            Route::resource('projectstages', ProjectstagesController::class);
            Route::post('/projectstages/order', [ProjectstagesController::class, 'order'])->name('projectstages.order')->middleware(['auth', XSS::class]);
            Route::post('projects/bug/kanban/order', [ProjectController::class, 'bugKanbanOrder'])->name('bug.kanban.order');
            Route::get('projects/{id}/bug/kanban', [ProjectController::class, 'bugKanban'])->name('task.bug.kanban');
            Route::get('projects/{id}/bug', [ProjectController::class, 'bug'])->name('task.bug');
            Route::get('projects/{id}/bug/create', [ProjectController::class, 'bugCreate'])->name('task.bug.create');
            Route::post('projects/{id}/bug/store', [ProjectController::class, 'bugStore'])->name('task.bug.store');
            Route::get('projects/{id}/bug/{bid}/edit', [ProjectController::class, 'bugEdit'])->name('task.bug.edit');
            Route::post('projects/{id}/bug/{bid}/update', [ProjectController::class, 'bugUpdate'])->name('task.bug.update');
            Route::delete('projects/{id}/bug/{bid}/destroy', [ProjectController::class, 'bugDestroy'])->name('task.bug.destroy');
            Route::get('projects/{id}/bug/{bid}/show', [ProjectController::class, 'bugShow'])->name('task.bug.show');
            Route::post('projects/{id}/bug/{bid}/comment', [ProjectController::class, 'bugCommentStore'])->name('bug.comment.store');
            Route::post('projects/bug/{bid}/file', [ProjectController::class, 'bugCommentStoreFile'])->name('bug.comment.file.store');
            Route::delete('projects/bug/comment/{id}', [ProjectController::class, 'bugCommentDestroy'])->name('bug.comment.destroy');
            Route::delete('projects/bug/file/{id}', [ProjectController::class, 'bugCommentDestroyFile'])->name('bug.comment.file.destroy');

            Route::resource('bugstatus', BugStatusController::class);
            Route::post('/bugstatus/order', [BugStatusController::class, 'order'])->name('bugstatus.order');
            Route::get('bugs-report/{view?}', [ProjectTaskController::class, 'allBugList'])->name('bugs.view')->middleware(['auth', XSS::class]);
        }
    );

    // User_Todo Module
    Route::post('/todo/create', [UserController::class, 'todo_store'])->name('todo.store')->middleware(['auth', XSS::class]);
    Route::post('/todo/{id}/update', [UserController::class, 'todo_update'])->name('todo.update')->middleware(['auth', XSS::class]);
    Route::delete('/todo/{id}', [UserController::class, 'todo_destroy'])->name('todo.destroy')->middleware(['auth', XSS::class]);
    Route::get('/change/mode', [UserController::class, 'changeMode'])->name('change.mode')->middleware(['auth', XSS::class]);
    Route::get('dashboard-view', [DashboardController::class, 'filterView'])->name('dashboard.view')->middleware(['auth', XSS::class]);
    Route::get('dashboard', [DashboardController::class, 'clientView'])->name('client.dashboard.view')->middleware(['auth', XSS::class]);

    // saas
    Route::resource('users', UserController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::resource('plans', PlanController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::resource('coupons', CouponController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    // Orders

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::get('/orders', [StripePaymentController::class, 'index'])->name('order.index');
            Route::get('/stripe/{code}', [StripePaymentController::class, 'stripe'])->name('stripe');
            Route::post('/stripe', [StripePaymentController::class, 'stripePost'])->name('stripe.post');
        }
    );

    Route::get('/apply-coupon', [CouponController::class, 'applyCoupon'])->name('apply.coupon')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    //================================= Form Builder ====================================//

    // Form Builder
    Route::resource('form_builder', FormBuilderController::class)->middleware(['auth', XSS::class]);

    // Form link base view


    // Form Field
    Route::get('/form_builder/{id}/field', [FormBuilderController::class, 'fieldCreate'])->name('form.field.create')->middleware(['auth', XSS::class]);
    Route::post('/form_builder/{id}/field', [FormBuilderController::class, 'fieldStore'])->name('form.field.store')->middleware(['auth', XSS::class]);
    Route::get('/form_builder/{id}/field/{fid}/show', [FormBuilderController::class, 'fieldShow'])->name('form.field.show')->middleware(['auth', XSS::class]);
    Route::get('/form_builder/{id}/field/{fid}/edit', [FormBuilderController::class, 'fieldEdit'])->name('form.field.edit')->middleware(['auth', XSS::class]);
    Route::post('/form_builder/{id}/field/{fid}', [FormBuilderController::class, 'fieldUpdate'])->name('form.field.update')->middleware(['auth', XSS::class]);
    Route::delete('/form_builder/{id}/field/{fid}', [FormBuilderController::class, 'fieldDestroy'])->name('form.field.destroy')->middleware(['auth', XSS::class]);

    // Form Response
    Route::get('/form_response/{id}', [FormBuilderController::class, 'viewResponse'])->name('form.response')->middleware(['auth', XSS::class]);
    Route::get('/response/{id}', [FormBuilderController::class, 'responseDetail'])->name('response.detail')->middleware(['auth', XSS::class]);

    // Form Field Bind
    Route::get('/form_field/{id}', [FormBuilderController::class, 'formFieldBind'])->name('form.field.bind')->middleware(['auth', XSS::class]);
    Route::post('/form_field_store/{id}}', [FormBuilderController::class, 'bindStore'])->name('form.bind.store')->middleware(['auth', XSS::class]);

    // contract

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::get('contract/{id}/description', [ContractController::class, 'description'])->name('contract.description');
            Route::get('contract/grid', [ContractController::class, 'grid'])->name('contract.grid');
            Route::resource('contract', ContractController::class);
        }
    );
    Route::post('/contract/{id}/file', [ContractController::class, 'fileUpload'])->name('contract.file.upload')->middleware(['auth', XSS::class]);
    Route::get('contract/pdf/{id}', [ContractController::class, 'pdffromcontract'])->name('contract.download.pdf')->middleware(['auth']);
    Route::get('contract/{id}/get_contract', [ContractController::class, 'printContract'])->name('get.contract')->middleware(['auth']);
    Route::post('/contract_status_edit/{id}', [ContractController::class, 'contract_status_edit'])->name('contract.status')->middleware(['auth', XSS::class]);
    Route::post('contract/{id}/contract_description', [ContractController::class, 'contract_descriptionStore'])->name('contract.contract_description.store')->middleware(['auth']);
    Route::get('/contract/{id}/file/{fid}', [ContractController::class, 'fileDownload'])->name('contracts.file.download')->middleware(['auth', XSS::class]);
    Route::delete('/contract/{id}/file/delete/{fid}', [ContractController::class, 'fileDelete'])->name('contracts.file.delete')->middleware(['auth', XSS::class]);
    Route::get('/contract/copy/{id}', [ContractController::class, 'copycontract'])->name('contract.copy')->middleware(['auth', XSS::class]);
    Route::post('/contract/copy/store', [ContractController::class, 'copycontractstore'])->name('contract.copy.store')->middleware(['auth', XSS::class]);
    Route::get('/contract/{id}/mail', [ContractController::class, 'sendmailContract'])->name('send.mail.contract');
    Route::get('/signature/{id}', [ContractController::class, 'signature'])->name('signature')->middleware(['auth']);
    Route::post('/signaturestore', [ContractController::class, 'signatureStore'])->name('signaturestore')->middleware(['auth', XSS::class]);
    Route::post('/contract/{id}/comment', [ContractController::class, 'commentStore'])->name('comment.store');
    Route::post('/contract/{id}/notes', [ContractController::class, 'noteStore'])->name('note_store.store')->middleware(['auth']);
    Route::delete('/contract/{id}/notes', [ContractController::class, 'noteDestroy'])->name('note_store.destroy')->middleware(['auth']);
    Route::delete('/contract/{id}/comment', [ContractController::class, 'commentDestroy'])->name('comment_store.destroy');
    Route::get('get-projects/{client_id}', [ContractController::class, 'clientByProject'])->name('project.by.user.id')->middleware(['auth', XSS::class]);

    // client wise project show in modal

    Route::any('/contract/clients/select/{bid}', [ContractController::class, 'clientwiseproject'])->name('contract.clients.select');

    // copy contract

    Route::get('/contract/copy/{id}', [ContractController::class, 'copycontract'])->name('contract.copy')->middleware(['auth', XSS::class]);
    Route::post('contract/copy/store', [ContractController::class, 'copycontractstore'])->name('contract.copy.store')->middleware(['auth', XSS::class]);

    // Custom Landing Page

    //    Route::get('/landingpage', [LandingPageSectionController::class, 'index'])->name('custom_landing_page.index')->middleware(['auth', XSS::class]);
    //    Route::get('/LandingPage/show/{id}', [LandingPageSectionController::class, 'show']);
    //
    //    Route::post('/LandingPage/setConetent', [LandingPageSectionController::class, 'setConetent'])->middleware(['auth', XSS::class]);
    //
    //
    //    Route::get(
    //        '/get_landing_page_section/{name}', function ($name) {
    //        $plans = \DB::table('plans')->get();
    //
    //        return view('custom_landing_page.' . $name, compact('plans'));
    //    }
    //    );
    //
    //    Route::post('/LandingPage/removeSection/{id}', [LandingPageSectionController::class, 'removeSection'])->middleware(['auth', XSS::class]);
    //    Route::post('/LandingPage/setOrder', [LandingPageSectionController::class, 'setOrder'])->middleware(['auth', XSS::class]);
    //    Route::post('/LandingPage/copySection', [LandingPageSectionController::class, 'copySection'])->middleware(['auth', XSS::class]);

    // Plan Payment Gateways
    Route::post('plan-pay-with-bank', [BankTransferPaymentController::class, 'planPayWithBank'])->name('plan.pay.with.bank')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::post('plan-pay-with-paypal', [PaypalController::class, 'planPayWithPaypal'])->name('plan.pay.with.paypal')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('{id}/plan-get-payment-status', [PaypalController::class, 'planGetPaymentStatus'])->name('plan.get.payment.status')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::post('/plan-pay-with-paystack', [PaystackPaymentController::class, 'planPayWithPaystack'])->name('plan.pay.with.paystack')->middleware(['auth', XSS::class]);
    Route::get('/plan/paystack/{pay_id}/{plan_id}', [PaystackPaymentController::class, 'getPaymentStatus'])->name('plan.paystack');

    Route::post('/plan-pay-with-flaterwave', [FlutterwavePaymentController::class, 'planPayWithFlutterwave'])->name('plan.pay.with.flaterwave')->middleware(['auth', XSS::class]);
    Route::get('/plan/flaterwave/{txref}/{plan_id}', [FlutterwavePaymentController::class, 'getPaymentStatus'])->name('plan.flaterwave');

    Route::post('/plan-pay-with-razorpay', [RazorpayPaymentController::class, 'planPayWithRazorpay'])->name('plan.pay.with.razorpay')->middleware(['auth', XSS::class]);
    Route::get('/plan/razorpay/{txref}/{plan_id}', [RazorpayPaymentController::class, 'getPaymentStatus'])->name('plan.razorpay');

    Route::post('/plan-pay-with-paytm', [PaytmPaymentController::class, 'planPayWithPaytm'])->name('plan.pay.with.paytm')->middleware(['auth', XSS::class]);
    Route::post('/plan/paytm/{plan}', [PaytmPaymentController::class, 'getPaymentStatus'])->name('plan.paytm');

    Route::post('/plan-pay-with-mercado', [MercadoPaymentController::class, 'planPayWithMercado'])->name('plan.pay.with.mercado')->middleware(['auth', XSS::class]);
    Route::get('/plan/mercado/{plan}/{amount}', [MercadoPaymentController::class, 'getPaymentStatus'])->name('plan.mercado');

    Route::post('/plan-pay-with-mollie', [MolliePaymentController::class, 'planPayWithMollie'])->name('plan.pay.with.mollie')->middleware(['auth', XSS::class]);
    Route::get('/plan/mollie/{plan}', [MolliePaymentController::class, 'getPaymentStatus'])->name('plan.mollie');

    Route::post('/plan-pay-with-skrill', [SkrillPaymentController::class, 'planPayWithSkrill'])->name('plan.pay.with.skrill')->middleware(['auth', XSS::class]);
    Route::get('/plan/skrill/{plan}', [SkrillPaymentController::class, 'getPaymentStatus'])->name('plan.skrill');

    Route::post('/plan-pay-with-coingate', [CoingatePaymentController::class, 'planPayWithCoingate'])->name('plan.pay.with.coingate')->middleware(['auth', XSS::class]);
    Route::get('/plan/coingate/{plan}', [CoingatePaymentController::class, 'getPaymentStatus'])->name('plan.coingate');

    Route::post('/toyyibpay', [ToyyibpayController::class, 'planPayWithToyyibpay'])->name('plan.toyyibpaypayment');
    Route::get('/plan-pay-with-toyyibpay/{id}/{status}/{coupon}', [ToyyibpayController::class, 'getPaymentStatus'])->name('plan.status');

    Route::post('payfast-plan', [PayFastController::class, 'planPayWithPayfast'])->name('payfast.payment');
    Route::get('payfast-plan/{success}', [PayFastController::class, 'getPaymentStatus'])->name('payfast.payment.success');

    Route::post('iyzipay/prepare', [IyziPayController::class, 'initiatePayment'])->name('iyzipay.payment.init');
    Route::post('iyzipay/callback/plan/{id}/{amount}/{coupan_code?}', [IyzipayController::class, 'iyzipayCallback'])->name('iyzipay.payment.callback');

    Route::post('/sspay', [SspayController::class, 'SspayPaymentPrepare'])->name('plan.sspaypayment');
    Route::get('sspay-payment-plan/{plan_id}/{amount}/{couponCode}', [SspayController::class, 'SspayPlanGetPayment'])->middleware(['auth'])->name('plan.sspay.callback');

    Route::post('plan-pay-with-paytab', [PaytabController::class, 'planPayWithpaytab'])->middleware(['auth'])->name('plan.pay.with.paytab');
    Route::any('paytab-success/plan', [PaytabController::class, 'PaytabGetPayment'])->middleware(['auth'])->name('plan.paytab.success');

    Route::any('/payment/initiate', [BenefitPaymentController::class, 'initiatePayment'])->name('plan.pay.with.benefit');
    Route::any('call_back', [BenefitPaymentController::class, 'call_back'])->name('benefit.call_back');

    Route::post('cashfree/payments/store', [CashfreeController::class, 'cashfreePaymentStore'])->name('plan.pay.with.cashfree');
    Route::any('cashfree/payments/success', [CashfreeController::class, 'cashfreePaymentSuccess'])->name('cashfreePayment.success');

    Route::post('/aamarpay/payment', [AamarpayController::class, 'pay'])->name('plan.pay.with.aamarpay');
    Route::any('/aamarpay/success/{data}', [AamarpayController::class, 'aamarpaysuccess'])->name('pay.aamarpay.success');

    Route::post('/paytr/payment/{plan_id}', [PaytrController::class, 'PlanpayWithPaytr'])->name('plan.pay.with.paytr');
    Route::get('/paytr/sussess/', [PaytrController::class, 'paytrsuccess'])->name('pay.paytr.success');

    Route::post('/plan/yookassa/payment', [YooKassaController::class, 'planPayWithYooKassa'])->name('plan.pay.with.yookassa');
    Route::get('/plan/yookassa/{plan}', [YooKassaController::class, 'planGetYooKassaStatus'])->name('plan.yookassa.status');

    Route::any('/midtrans', [MidtransPaymentController::class, 'planPayWithMidtrans'])->name('plan.pay.with.midtrans');
    Route::any('/midtrans/callback', [MidtransPaymentController::class, 'planGetMidtransStatus'])->name('plan.get.midtrans.status');

    Route::any('/xendit/payment', [XenditPaymentController::class, 'planPayWithXendit'])->name('plan.pay.with.xendit');
    Route::any('/xendit/payment/status', [XenditPaymentController::class, 'planGetXenditStatus'])->name('plan.xendit.status');

    //plan-order
    Route::post('order/{id}/changeaction', [BankTransferPaymentController::class, 'changeStatus'])->name('order.changestatus');
    Route::delete('order/{id}', [BankTransferPaymentController::class, 'orderDestroy'])->name('order.destroy');
    Route::get('order/{id}/action', [BankTransferPaymentController::class, 'action'])->name('order.action');

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::get('order', [StripePaymentController::class, 'index'])->name('order.index');
            Route::get('/stripe/{code}', [StripePaymentController::class, 'stripe'])->name('stripe');
            Route::post('/stripe', [StripePaymentController::class, 'stripePost'])->name('stripe.post');
        }
    );
    //    Route::post('plan-pay-with-paypal', [PaypalController::class, 'planPayWithPaypal'])->name('plan.pay.with.paypal')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    //    Route::get('{id}/plan-get-payment-status', [PaypalController::class, 'planGetPaymentStatus'])->name('plan.get.payment.status')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::get('support/{id}/reply', [SupportController::class, 'reply'])->name('support.reply');
            Route::post('support/{id}/reply', [SupportController::class, 'replyAnswer'])->name('support.reply.answer');
            Route::get('support/grid', [SupportController::class, 'grid'])->name('support.grid');
            Route::resource('support', SupportController::class);
        }
    );

    Route::resource('competencies', CompetenciesController::class)->middleware(['auth', XSS::class]);

    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::resource('performanceType', PerformanceTypeController::class);
        }
    );

    // Plan Request Module
    Route::get('plan_request', [PlanRequestController::class, 'index'])->name('plan_request.index')->middleware(['auth', XSS::class]);
    Route::get('request_frequency/{id}', [PlanRequestController::class, 'requestView'])->name('request.view')->middleware(['auth', XSS::class]);
    Route::get('request_send/{id}', [PlanRequestController::class, 'userRequest'])->name('send.request')->middleware(['auth', XSS::class]);
    Route::get('request_response/{id}/{response}', [PlanRequestController::class, 'acceptRequest'])->name('response.request')->middleware(['auth', XSS::class]);
    Route::get('request_cancel/{id}', [PlanRequestController::class, 'cancelRequest'])->name('request.cancel')->middleware(['auth', XSS::class]);
    //QR Code Module

    // Import/Export Data Route

    Route::get('export/productservice', [ProductServiceController::class, 'export'])->name('productservice.export');
    Route::get('import/productservice/file', [ProductServiceController::class, 'importFile'])->name('productservice.file.import');
    Route::get('import/productservice/sample', [ProductServiceController::class, 'downloadProductImportSample'])->name('productservice.import.sample');
    Route::get('import/stock/subproduct/file', [ProductServiceController::class, 'stockSubProductImportFile'])->name('productservice.stock.subproduct.import.file');
    Route::get('import/stock/subproduct/sample', [ProductServiceController::class, 'downloadStockSubproductImportSample'])->name('productservice.stock.subproduct.sample');
    Route::get('import/subproduct/file', [SubProductController::class, 'importFile'])->name('subproduct.file.import');
    Route::get('import/stock/file', [ProductServiceController::class, 'stockImportFile'])->name('productservice.stock.import.file');
    Route::get('import/spare-parts-stock/file', [ProductServiceController::class, 'sparePartsStockImportFile'])->name('productservice.spare.parts.stock.import.file');
    Route::get('import/spare-parts-stock/sample', [ProductServiceController::class, 'downloadSparePartsStockSample'])->name('productservice.spare.parts.stock.sample');
    Route::post('import/productservice', [ProductServiceController::class, 'import'])->name('productservice.import');
    Route::post('import/subproductservice', [SubProductController::class, 'import'])->name('subproductservice.import');
    Route::post('import/stock', [ProductServiceController::class, 'stockImport'])->name('productservice.stock.import');
    Route::post('import/spare-parts-stock', [ProductServiceController::class, 'sparePartsStockImport'])->name('productservice.spare.parts.stock.import');
    Route::get('export/customer', [CustomerController::class, 'export'])->name('customer.export');
    Route::get('import/customer/file', [CustomerController::class, 'importFile'])->name('customer.file.import');
    Route::post('import/customer', [CustomerController::class, 'import'])->name('customer.import');
    Route::get('export/vender', [VenderController::class, 'export'])->name('vender.export');
    Route::get('import/vender/file', [VenderController::class, 'importFile'])->name('vender.file.import');
    Route::post('import/vender', [VenderController::class, 'import'])->name('vender.import');
    Route::get('export/invoice', [InvoiceController::class, 'export'])->name('invoice.export');
    Route::get('import/invoice/file', [InvoiceController::class, 'importFile'])->name('invoice.file.import');
    Route::get('import/invoice/sample', [InvoiceController::class, 'downloadSample'])->name('invoice.sample.download');
    Route::post('import/invoice', [InvoiceController::class, 'import'])->name('invoice.import');
    Route::get('export/proposal', [ProposalController::class, 'export'])->name('proposal.export');
    Route::get('export/bill', [BillController::class, 'export'])->name('bill.export');
    Route::get('export/bill-products/{id}', [BillController::class, 'exportProducts'])->name('bill.export.products');

    Route::get('export/employee', [EmployeeController::class, 'export'])->name('employee.export');
    Route::get('import/employee/file', [EmployeeController::class, 'importFile'])->name('employee.file.import');
    Route::post('import/employee', [EmployeeController::class, 'import'])->name('employee.import');

    Route::get('import/attendance/file', [AttendanceEmployeeController::class, 'importFile'])->name('attendance.file.import');
    Route::post('import/attendance', [AttendanceEmployeeController::class, 'import'])->name('attendance.import');

    Route::get('export/transaction', [TransactionController::class, 'export'])->name('transaction.export');
    Route::get('export/accountstatement', [ReportController::class, 'export'])->name('accountstatement.export');
    Route::get('export/productstock', [ReportController::class, 'stock_export'])->name('productstock.export');
    Route::get('export/payroll', [ReportController::class, 'PayrollReportExport'])->name('payroll.export');
    Route::get('export/leave', [ReportController::class, 'LeaveReportExport'])->name('leave.export');

    Route::post('export/payslip', [PaySlipController::class, 'export'])->name('payslip.export');

    // Time-Tracker
    Route::post('stop-tracker', [DashboardController::class, 'stopTracker'])->name('stop.tracker')->middleware(['auth', XSS::class]);
    Route::get('time-tracker', [TimeTrackerController::class, 'index'])->name('time.tracker')->middleware(['auth', XSS::class]);
    Route::delete('tracker/{tid}/destroy', [TimeTrackerController::class, 'Destroy'])->name('tracker.destroy');
    Route::post('tracker/image-view', [TimeTrackerController::class, 'getTrackerImages'])->name('tracker.image.view');
    Route::delete('tracker/image-remove', [TimeTrackerController::class, 'removeTrackerImages'])->name('tracker.image.remove');
    Route::get('projects/time-tracker/{id}', [ProjectController::class, 'tracker'])->name('projecttime.tracker')->middleware(['auth', XSS::class]);

    // Zoom Meeting
    Route::resource('zoom-meeting', ZoomMeetingController::class)->middleware(['auth', XSS::class]);
    Route::any('/zoom-meeting/projects/select/{bid}', [ZoomMeetingController::class, 'projectwiseuser'])->name('zoom-meeting.projects.select');
    Route::get('zoom-meeting-calender', [ZoomMeetingController::class, 'calender'])->name('zoom-meeting.calender')->middleware(['auth', XSS::class]);

    // PaymentWall

    Route::post('/paymentwalls', [PaymentWallPaymentController::class, 'paymentwall'])->name('plan.paymentwallpayment')->middleware([XSS::class]);
    Route::post('/plan-pay-with-paymentwall/{plan}', [PaymentWallPaymentController::class, 'planPayWithPaymentWall'])->name('plan.pay.with.paymentwall')->middleware(['auth', XSS::class]);
    Route::get('/plan/{flag}', [PaymentWallPaymentController::class, 'planeerror'])->name('error.plan.show');

    //POS System

    Route::get('warehouse/stock-count-imports', [WarehouseStockCountImportController::class, 'index'])
        ->name('warehouse.stock-count-imports.index')
        ->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('warehouse/stock-count-imports/{warehouseStockCountImport}/export-lines', [WarehouseStockCountImportController::class, 'exportLines'])
        ->name('warehouse.stock-count-imports.export-lines')
        ->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('warehouse/stock-count-imports/{warehouseStockCountImport}', [WarehouseStockCountImportController::class, 'show'])
        ->name('warehouse.stock-count-imports.show')
        ->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::resource('warehouse', WarehouseController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('warehouse/{warehouse}/stock-count', [WarehouseController::class, 'stockCount'])->name('warehouse.stock-count')->middleware(['auth', \App\Http\Middleware\XSS::class]);
    Route::post('warehouse/{warehouse}/stock-count', [WarehouseController::class, 'storeStockCount'])->name('warehouse.stock-count.store')->middleware(['auth', \App\Http\Middleware\XSS::class]);
    Route::post('warehouse/{warehouse}/stock-count-apply-import', [WarehouseController::class, 'applyStockCountFromImport'])->name('warehouse.stock-count.apply-import')->middleware(['auth', \App\Http\Middleware\XSS::class]);
    Route::get('warehouse/stock-count/import-status/{token}', [WarehouseController::class, 'stockCountImportStatus'])->name('warehouse.stock-count.import-status')->middleware(['auth', \App\Http\Middleware\XSS::class]);
    Route::post('warehouse/stock-count/get-categories', [WarehouseController::class, 'getStockCountCategories'])->name('warehouse.stock-count.get-categories')->middleware(['auth', \App\Http\Middleware\XSS::class]);
    Route::post('warehouse/stock-count/get-brands', [WarehouseController::class, 'getStockCountBrands'])->name('warehouse.stock-count.get-brands')->middleware(['auth', \App\Http\Middleware\XSS::class]);
    Route::post('warehouse/stock-count/get-products', [WarehouseController::class, 'getStockCountProducts'])->name('warehouse.stock-count.get-products')->middleware(['auth', \App\Http\Middleware\XSS::class]);
    Route::get('warehouse/stock-count/import', [WarehouseController::class, 'showImportStockCount'])->name('warehouse.stock-count.import')->middleware(['auth', \App\Http\Middleware\XSS::class]);
    Route::post('warehouse/stock-count/import', [WarehouseController::class, 'importStockCount'])->name('warehouse.stock-count.import.process')->middleware(['auth', \App\Http\Middleware\XSS::class]);
    Route::get('warehouse/{warehouse}/stock-count/import-single', [WarehouseController::class, 'showImportStockCountSingle'])->name('warehouse.stock-count.import-single')->middleware(['auth', \App\Http\Middleware\XSS::class]);
    Route::post('warehouse/{warehouse}/stock-count/import-single', [WarehouseController::class, 'importStockCountSingle'])->name('warehouse.stock-count.import-single.process')->middleware(['auth', \App\Http\Middleware\XSS::class]);
    Route::get('warehouse/{warehouse}/stock-count/export-errors', [WarehouseController::class, 'exportStockCountErrors'])->name('warehouse.stock-count.export-errors')->middleware(['auth', \App\Http\Middleware\XSS::class]);
    Route::get('/master-ledger', [MasterlistLedgerController::class,'index'])->name('master-ledger.index');
    Route::get('/master-ledger/records',[MasterlistLedgerController::class,'records'])->name('master-ledger.records');
    Route::get('/master-ledger/stock', [MasterlistLedgerController::class,'stock'])->name('master-ledger.stock');
    Route::get('master-ledger/invoice/{id}', [InvoiceController::class, 'show2'])->name('rentinvoice.show2');
    Route::get('master-ledger/onorder/', [MasterlistLedgerController::class, 'onorder_details'])->name('master-ledger.onorder_details');
    
    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::get('purchase/items', [PurchaseController::class, 'items'])->name('purchase.items');
            Route::resource('purchase', PurchaseController::class);

            //    Route::get('/bill/{id}/', 'PurchaseController@purchaseLink')->name('purchase.link.copy');
            Route::get('purchase/{id}/payment', [PurchaseController::class, 'payment'])->name('purchase.payment');
            Route::post('purchase/{id}/payment', [PurchaseController::class, 'createPayment'])->name('purchase.payment');
            Route::post('purchase/{id}/payment/{pid}/destroy', [PurchaseController::class, 'paymentDestroy'])->name('purchase.payment.destroy');
            Route::post('purchase/product/destroy', [PurchaseController::class, 'productDestroy'])->name('purchase.product.destroy');
            Route::post('purchase/vender', [PurchaseController::class, 'vender'])->name('purchase.vender');
            Route::post('purchase/product', [PurchaseController::class, 'product'])->name('purchase.product');
            Route::get('purchase/create/{cid}', [PurchaseController::class, 'create'])->name('purchase.create');
            Route::get('purchase/{id}/sent', [PurchaseController::class, 'sent'])->name('purchase.sent');
            Route::get('purchase/{id}/resent', [PurchaseController::class, 'resent'])->name('purchase.resent');
        }

    );
    Route::get('pos-print-setting', [SystemController::class, 'posPrintIndex'])->name('pos.print.setting')->middleware(['auth', XSS::class]);
    Route::get('purchase/preview/{template}/{color}', [PurchaseController::class, 'previewPurchase'])->name('purchase.preview')->middleware(['auth', XSS::class]);
    Route::get('pos/preview/{template}/{color}', [PosController::class, 'previewPos'])->name('pos.preview')->middleware(['auth', XSS::class]);


    Route::post('/purchase/template/setting', [PurchaseController::class, 'savePurchaseTemplateSettings'])->name('purchase.template.setting');
    Route::post('/pos/template/setting', [PosController::class, 'savePosTemplateSettings'])->name('pos.template.setting');
    

    Route::get('purchase/pdf/{id}', [PurchaseController::class, 'purchase'])->name('purchase.pdf')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('pos/pdf/{id}', [PosController::class, 'pos'])->name('pos.pdf')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('pos/data/store', [PosController::class, 'store'])->name('pos.data.store')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    //for pos print
    Route::get('printview/pos', [PosController::class, 'printView'])->name('pos.printview')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::post('printview/pos/direct', [PosController::class, 'directPrint'])->name('pos.printview.direct')->middleware(['auth']);
    // Print queue API endpoints moved to routes/api.php (no CSRF protection needed)
    // Web-based print service page (for Chrome OS)
    Route::get('pos/print-service', [PosController::class, 'printServicePage'])->name('pos.print-service')->middleware(['auth']);
    Route::get('temp-printview/pos', [PosController::class, 'printtemp'])->name('pos.temp-printview')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    // Specific POS routes must be defined before resource route to avoid conflicts
    Route::get('pos/logs', [PosController::class, 'logs'])->name('pos.logs')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('pos/stock-report', [SubProductController::class, 'posStockReport'])->name('pos.stock-report')->middleware(['auth', XSS::class]);
    Route::post('pos/{id}/update-date', [PosController::class, 'updatePosDate'])->name('pos.update-date')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::resource('pos', PosController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

    Route::get('product-categories', [ProductServiceCategoryController::class, 'getProductCategories'])->name('product.categories')->middleware(['auth', XSS::class]);
    Route::get('add-to-cart/{W_id}/{P_num}/{session}', [ProductServiceController::class, 'addToCart'])->middleware(['auth', XSS::class]);
    Route::patch('update-cart', [ProductServiceController::class, 'updateCart'])->middleware(['auth', XSS::class]);
    Route::delete('remove-from-cart', [ProductServiceController::class, 'removeFromCart'])->middleware(['auth', XSS::class]);
    Route::post('update-discount', [PosController::class, 'updateDiscount'])->middleware(['auth', XSS::class]);


    Route::get('name-search-products', [ProductServiceCategoryController::class, 'searchProductsByName'])->name('name.search.products')->middleware(['auth', XSS::class]);
    Route::get('search-products', [ProductServiceController::class, 'searchProducts'])->name('search.products')->middleware(['auth', XSS::class]);
    Route::get('search-barcode', [ProductServiceController::class, 'searchBarcode'])->name('search.barcode')->middleware(['auth', XSS::class]);
    Route::any('report/pos', [PosController::class, 'report'])->name('pos.report')->middleware(['auth', XSS::class]);
    Route::get('report/pos/export', [PosController::class, 'exportReport'])->name('pos.report.export')->middleware(['auth', XSS::class]);
    Route::get('report/pos/print', [PosController::class, 'printReport'])->name('pos.report.print')->middleware(['auth', XSS::class]);

    //warehouse-transfer
    Route::resource('warehouse-transfer', WarehouseTransferController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::post('warehouse-transfer/{warehouseTransfer}/approve', [WarehouseTransferController::class, 'approve'])->name('warehouse-transfer.approve')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::post('warehouse-transfer/approve-all', [WarehouseTransferController::class, 'approveAll'])->name('warehouse-transfer.approve-all')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    
    //warehouse-transfer-request
    Route::get('warehouse-transfer-request', [WarehouseTransferController::class, 'requestIndex'])->name('warehouse-transfer-request.index')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('warehouse-transfer-request/{request}', [WarehouseTransferController::class, 'showRequest'])->name('warehouse-transfer-request.show')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::get('warehouse-transfer-request/{request}/print', [WarehouseTransferController::class, 'printRequest'])->name('warehouse-transfer-request.print')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::post('warehouse-transfer-request/{request}/attachment', [WarehouseTransferController::class, 'uploadRequestAttachment'])->name('warehouse-transfer-request.attachment.upload')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::post('warehouse-transfer-request/{request}/approve', [WarehouseTransferController::class, 'approveRequest'])->name('warehouse-transfer-request.approve')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::delete('warehouse-transfer-request/{request}', [WarehouseTransferController::class, 'destroyRequest'])->name('warehouse-transfer-request.destroy')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::post('warehouse-transfer/{transfer}/update-quantity', [WarehouseTransferController::class, 'updateTransferQuantity'])->name('warehouse-transfer.update-quantity')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
    Route::post('warehouse-transfer/get-categories', [WarehouseTransferController::class, 'getCategories'])->name('warehouse-transfer.get-categories')->middleware(['auth', XSS::class]);
    Route::post('warehouse-transfer/get-brands', [WarehouseTransferController::class, 'getBrands'])->name('warehouse-transfer.get-brands')->middleware(['auth', XSS::class]);
    Route::post('warehouse-transfer/get-products', [WarehouseTransferController::class, 'getProducts'])->name('warehouse-transfer.get-products')->middleware(['auth', XSS::class]);
    Route::post('warehouse-transfer/get-sub-products', [WarehouseTransferController::class, 'getSubProducts'])->name('warehouse-transfer.get-sub-products')->middleware(['auth', XSS::class]);
    Route::post('warehouse-transfer/get-sub-products-by-category', [WarehouseTransferController::class, 'getSubProductsByCategory'])->name('warehouse-transfer.get-sub-products-by-category')->middleware(['auth', XSS::class]);
    Route::post('warehouse-transfer/getproduct', [WarehouseTransferController::class, 'getproduct'])->name('warehouse-transfer.getproduct')->middleware(['auth', XSS::class]);
    Route::post('warehouse-transfer/getquantity', [WarehouseTransferController::class, 'getquantity'])->name('warehouse-transfer.getquantity')->middleware(['auth', XSS::class]);
    Route::get('import/warehouse-transfer/file', [WarehouseTransferController::class, 'importFile'])->name('warehouse-transfer.file.import')->middleware(['auth', XSS::class]);
    Route::post('import/warehouse-transfer', [WarehouseTransferController::class, 'import'])->name('warehouse-transfer.import')->middleware(['auth', XSS::class]);
    Route::get('warehouse-transfer/sample/download', [WarehouseTransferController::class, 'downloadSample'])->name('warehouse-transfer.sample.download')->middleware(['auth', XSS::class]);

    Route::get('warehoustrans/create',[WarehouseTransferController::class, 'create'])->name('warehoustrans.create');
    //pos barcode
    
    Route::get('setting/pos', [PosController::class, 'setting'])->name('pos.setting')->middleware(['auth', XSS::class]);
    Route::post('barcode/settings', [PosController::class, 'BarcodesettingStore'])->name('barcode.setting');
    Route::post('pos/getproduct', [PosController::class, 'getproduct'])->name('pos.getproduct')->middleware(['auth', XSS::class]);
    Route::get('pos/get-cashiers', [PosController::class, 'getCashiersForWarehouseAjax'])->name('pos.get-cashiers')->middleware(['auth', XSS::class]);
    Route::post('pos/barcode/get-categories', [PosController::class, 'getBarcodeCategories'])->name('pos.barcode.get-categories')->middleware(['auth', XSS::class]);
    Route::post('pos/barcode/get-brands', [PosController::class, 'getBarcodeBrands'])->name('pos.barcode.get-brands')->middleware(['auth', XSS::class]);
    Route::post('pos/barcode/get-products', [PosController::class, 'getBarcodeProducts'])->name('pos.barcode.get-products')->middleware(['auth', XSS::class]);
    Route::post('pos/barcode/get-sub-products', [PosController::class, 'getBarcodeSubProducts'])->name('pos.barcode.get-sub-products')->middleware(['auth', XSS::class]);
    Route::post('pos/barcode/search-direct', [PosController::class, 'searchBarcodeDirect'])->name('pos.barcode.search-direct')->middleware(['auth', XSS::class]);
    Route::get('pos/barcode/mobile-scan', [PosController::class, 'mobileBarcodeScan'])->name('pos.barcode.mobile-scan')->middleware(['auth', XSS::class]);
    Route::post('pos/barcode/mobile-scan', [PosController::class, 'mobileBarcodeScanSearch'])->name('pos.barcode.mobile-scan.search')->middleware(['auth', XSS::class]);
    Route::post('pos/barcode/save-note', [PosController::class, 'saveSubProductNote'])->name('pos.barcode.save-note')->middleware(['auth', XSS::class]);
    Route::post('pos/getcustomfields', [PosController::class, 'getCustomFields'])->name('pos.getcustomfields')->middleware(['auth', XSS::class]);
    Route::any('pos-receipt', [PosController::class, 'receipt'])->name('pos.receipt')->middleware(['auth', XSS::class]);
    Route::post('/cartdiscount', [PosController::class, 'cartdiscount'])->name('cartdiscount')->middleware(['auth', XSS::class]);

    Route::get('print/pos', [PosController::class, 'printBarcode'])->name('pos.barcode')->middleware(['auth', XSS::class]);
    // Route::get('barcode/pos', [PosController::class, 'barcode'])->name('pos.barcode')->middleware(['auth', XSS::class]);

    // new print 
    Route::get('barcode/pos', [TicketController::class, 'printBarcode'])->name('pos.print')->middleware(['auth', XSS::class]);


    //Storage Setting

    Route::post('storage-settings', [SystemController::class, 'storageSettingStore'])->name('storage.setting.store')->middleware(['auth', XSS::class]);

    //appricalStar

    Route::post('/appraisals', [AppraisalController::class, 'empByStar'])->name('empByStar')->middleware(['auth', XSS::class]);
    Route::post('/appraisals1', [AppraisalController::class, 'empByStar1'])->name('empByStar1')->middleware(['auth', XSS::class]);
    Route::post('/getemployee', [AppraisalController::class, 'getemployee'])->name('getemployee');

    //offer Letter

    Route::post('setting/offerlatter/{lang?}', [SystemController::class, 'offerletterupdate'])->name('offerlatter.update');
    Route::get('setting/offerlatter', [SystemController::class, 'companyIndex'])->name('get.offerlatter.language');
    Route::get('job-onboard/pdf/{id}', [JobApplicationController::class, 'offerletterPdf'])->name('offerlatter.download.pdf');
    Route::get('job-onboard/doc/{id}', [JobApplicationController::class, 'offerletterDoc'])->name('offerlatter.download.doc');

    //joining Letter
    Route::post('setting/joiningletter/{lang?}', [SystemController::class, 'joiningletterupdate'])->name('joiningletter.update');
    Route::get('setting/joiningletter/', [SystemController::class, 'companyIndex'])->name('get.joiningletter.language');
    Route::get('employee/pdf/{id}', [EmployeeController::class, 'joiningletterPdf'])->name('joiningletter.download.pdf');
    Route::get('employee/doc/{id}', [EmployeeController::class, 'joiningletterDoc'])->name('joininglatter.download.doc');

    //Experience Certificate

    Route::post('setting/exp/{lang?}', [SystemController::class, 'experienceCertificateupdate'])->name('experiencecertificate.update');
    Route::get('setting/exp', [SystemController::class, 'companyIndex'])->name('get.experiencecertificate.language');
    Route::get('employee/exppdf/{id}', [EmployeeController::class, 'ExpCertificatePdf'])->name('exp.download.pdf');
    Route::get('employee/expdoc/{id}', [EmployeeController::class, 'ExpCertificateDoc'])->name('exp.download.doc');

    //NOC

    Route::post('setting/noc/{lang?}', [SystemController::class, 'NOCupdate'])->name('noc.update');
    Route::get('setting/noc', [SystemController::class, 'companyIndex'])->name('get.noc.language');
    Route::get('employee/nocpdf/{id}', [EmployeeController::class, 'NocPdf'])->name('noc.download.pdf');
    Route::get('employee/nocdoc/{id}', [EmployeeController::class, 'NocDoc'])->name('noc.download.doc');

    //Project Reports
    Route::resource('/project_report', ProjectReportController::class)->middleware(['auth', XSS::class]);
    Route::post('/project_report_data', [ProjectReportController::class, 'ajax_data'])->name('projects.ajax')->middleware(['auth', XSS::class]);
    Route::post('/project_report/tasks/{id}', [ProjectReportController::class, 'ajax_tasks_report'])->name('tasks.report.ajaxdata')->middleware(['auth', XSS::class]);
    Route::get('export/task_report/{id}', [ProjectReportController::class, 'export'])->name('project_report.export');

    //project copy module
    Route::get('/project/copy/{id}', [ProjectController::class, 'copyproject'])->name('project.copy')->middleware(['auth', XSS::class]);
    Route::post('/project/copy/store/{id}', [ProjectController::class, 'copyprojectstore'])->name('project.copy.store')->middleware(['auth', XSS::class]);

    //Google Calendar
    Route::any('event/get_event_data', [EventController::class, 'get_event_data'])->name('event.get_event_data')->middleware(['auth', XSS::class]);

    Route::post('setting/google-calender', [SystemController::class, 'saveGoogleCalenderSettings'])->name('google.calender.settings');
    Route::any('holiday/get_holiday_data', [HolidayController::class, 'get_holiday_data'])->name('holiday.get_holiday_data')->middleware(['auth', XSS::class]);
    Route::any('interview-schedule/get_interview_data', [InterviewScheduleController::class, 'get_interview_data'])->name('holiday.get_interview_data')->middleware(['auth', XSS::class]);
    Route::post('calendar/get_task_data', [ProjectTaskController::class, 'get_task_data'])->name('task.calendar.get_task_data')->middleware(['auth', XSS::class]);
    Route::any('zoom-meeting/get_zoom_meeting_data', [ZoomMeetingController::class, 'get_zoom_meeting_data'])->name('zoom-meeting.get_zoom_meeting_data')->middleware(['auth', XSS::class]);

    Route::any('meeting/get_meeting_data', [MeetingController::class, 'get_meeting_data'])->name('meeting.get_meeting_data')->middleware(['auth', XSS::class]);
    Route::get('meeting-calender', [MeetingController::class, 'calender'])->name('meeting.calender')->middleware(['auth', XSS::class]);

    Route::any('event/get_dashboard_event_data', [EventController::class, 'get_dashboard_event_data'])->name('event.get_dashboard_event_data')->middleware(['auth', XSS::class]);

    //branch wise department get in attendance report
    Route::post('reports-monthly-attendance/getdepartment', [ReportController::class, 'getdepartment'])->name('report.attendance.getdepartment')->middleware(['auth', XSS::class]);
    Route::post('reports-monthly-attendance/getemployee', [ReportController::class, 'getemployee'])->name('report.attendance.getemployee')->middleware(['auth', XSS::class]);

    //shared project & copy link
    Route::any('/projects/copy/link/{id}', [ProjectController::class, 'copylinksetting'])->name('projects.copy.link');
    Route::any('/projects{id}/settingcreate', [ProjectController::class, 'copylink_setting_create'])->name('projects.copylink.setting.create');
    Route::get('/shareproject/{lang?}', [ProjectController::class, 'shareproject'])->name('shareproject');

    //User Log
    Route::get('/userlogs', [UserController::class, 'userLog'])->name('user.userlog')->middleware(['auth', XSS::class]);
    Route::get('userlogs/{id}', [UserController::class, 'userLogView'])->name('user.userlogview')->middleware(['auth', XSS::class]);
    Route::delete('userlogs/{id}', [UserController::class, 'userLogDestroy'])->name('user.userlogdestroy')->middleware(['auth', XSS::class]);

    //notification Template
    Route::get('notification_templates/{id?}/{lang?}', [NotificationTemplatesController::class, 'index'])->name('notification_templates.index')->middleware(['auth', XSS::class]);
    Route::resource('notification-templates', NotificationTemplatesController::class)->middleware(['auth', XSS::class]);

    //Proposal/Invoice/Bill/Purchase/POS - footer notes
    Route::post('system-settings/note', [SystemController::class, 'footerNoteStore'])->name('system.settings.footernote')->middleware(['auth', XSS::class]);

    //AI module
    Route::post('chatgpt-settings', [SystemController::class, 'chatgptSetting'])->name('chatgpt.settings');
    Route::get('generate/{template_name}', [AiTemplateController::class, 'create'])->name('generate');
    Route::post('generate/keywords/{id}', [AiTemplateController::class, 'getKeywords'])->name('generate.keywords');
    Route::post('generate/response', [AiTemplateController::class, 'AiGenerate'])->name('generate.response');

    //AI module for grammar check
    Route::get('grammar/{template}', [AiTemplateController::class, 'grammar'])->name('grammar')->middleware(['auth', XSS::class]);
    Route::post('grammar/response', [AiTemplateController::class, 'grammarProcess'])->name('grammar.response')->middleware(['auth', XSS::class]);

    //IP-Restrication settings
    Route::get('create/ip', [SystemController::class, 'createIp'])->name('create.ip')->middleware(['auth', XSS::class]);
    Route::post('create/ip', [SystemController::class, 'storeIp'])->name('store.ip')->middleware(['auth', XSS::class]);
    Route::get('edit/ip/{id}', [SystemController::class, 'editIp'])->name('edit.ip')->middleware(['auth', XSS::class]);
    Route::post('edit/ip/{id}', [SystemController::class, 'updateIp'])->name('update.ip')->middleware(['auth', XSS::class]);
    Route::delete('destroy/ip/{id}', [SystemController::class, 'destroyIp'])->name('destroy.ip')->middleware(['auth', XSS::class]);

    //lang enable / disable
    Route::post('disable-language', [LanguageController::class, 'disableLang'])->name('disablelanguage')->middleware(['auth', XSS::class]);

    //Expense Module
    Route::get('expense/pdf/{id}', [ExpenseController::class, 'expense'])->name('expense.pdf')->middleware([XSS::class, RevalidateBackHistory::class]);
    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::get('expense/index', [ExpenseController::class, 'index'])->name('expense.index');
            Route::any('expense/customer', [ExpenseController::class, 'customer'])->name('expense.customer');
            Route::post('expense/vender', [ExpenseController::class, 'vender'])->name('expense.vender');
            Route::post('expense/employee', [ExpenseController::class, 'employee'])->name('expense.employee');

            Route::post('expense/product/destroy', [ExpenseController::class, 'productDestroy'])->name('expense.product.destroy');

            Route::post('expense/product', [ExpenseController::class, 'product'])->name('expense.product');
            Route::get('expense/items', [ExpenseController::class, 'items'])->name('expense.items');
            Route::get('expense-payment/{id}', [ExpenseController::class, 'payment'])->name('expense.payment');
            Route::post('expense/{id}/add-payment', [ExpenseController::class, 'addPayment'])->name('expense.addPayment');

            Route::resource('expense', ExpenseController::class);
            Route::get('expense/create/{cid}', [ExpenseController::class, 'create'])->name('expense.create');
        }
    );

    // Service Bill module (routes: simple-expense)
    Route::group(
        [
            'middleware' => [
                'auth',
                XSS::class,
                RevalidateBackHistory::class,
            ],
        ],
        function () {
            Route::resource('simple-expense', SimpleExpenseController::class);
            Route::get('simple-expense/ledger/{id}', [SimpleExpenseController::class, 'simple_expense_ledger'])->name('simple-expense.ledger')->middleware(['auth', XSS::class]);
            
            // Service Bill payments
            Route::get('simple-expense-payments', [SimpleExpensePaymentController::class, 'index'])->name('simple-expense-payments.index');
            Route::get('simple-expense-payments/create', [SimpleExpensePaymentController::class, 'create'])->name('simple-expense-payments.create');
            Route::post('simple-expense-payments', [SimpleExpensePaymentController::class, 'store'])->name('simple-expense-payments.store');
            Route::get('simple-expense-payments/{id}', [SimpleExpensePaymentController::class, 'show'])->name('simple-expense-payments.show');
            Route::get('simple-expense-payments/{id}/edit', [SimpleExpensePaymentController::class, 'edit'])->name('simple-expense-payments.edit');
            Route::put('simple-expense-payments/{id}', [SimpleExpensePaymentController::class, 'update'])->name('simple-expense-payments.update');
            Route::delete('simple-expense-payments/{id}', [SimpleExpensePaymentController::class, 'destroy'])->name('simple-expense-payments.destroy');
            Route::post('simple-expense-payments/{id}/send', [SimpleExpensePaymentController::class, 'sendPayment'])->name('simple-expense-payments.send');
        }
    );

    Route::get('stock_report', [SubProductController::class, 'stockReport'])->name('subproduct.stock_report');
    Route::get('stock_report/export', [SubProductController::class, 'stockReportExport'])->name('subproduct.stock_report.export')->middleware(['auth']);
    Route::get('stock_report/test-ids', [SubProductController::class, 'testStockReportExportIds'])->name('subproduct.stock_report.test_ids')->middleware(['auth']);
    Route::post('stock_report/import', [SubProductController::class, 'stockReportImport'])->name('subproduct.stock_report.import')->middleware(['auth']);
    Route::get('stock_report/sub-product/{id}/gallery', [SubProductController::class, 'stockReportSubProductGallery'])->name('subproduct.stock_report.gallery');
    Route::get('stock_report/sub-product/{id}/brochure.pdf', [SubProductController::class, 'stockReportSubProductBrochurePdf'])->name('subproduct.stock_report.brochure.pdf');
    Route::get('sell_report', [SubProductController::class, 'sellReport'])->name('subproduct.sell_report');
    Route::get('stock_movement_report', [SubProductController::class, 'stockMovementReport'])->name('subproduct.stock_movement_report');
    Route::get('stock_movement_report/export', [SubProductController::class, 'exportStockMovementReport'])->name('subproduct.stock_movement_report.export');
});


Route::group(
    [
        'middleware' => [
            'auth',
            XSS::class,
            RevalidateBackHistory::class,
        ],
    ],
    function () {
        Route::any('/cookie-consent', [SystemController::class, 'CookieConsent'])->name('cookie-consent');

        Route::get('/get-sub-product-custom-fields', [SubProductController::class, 'getSubProductCustomFields'])->name('get-sub-product-custom-fields');
        Route::get('subProducts/{id}', [SubProductController::class, 'index'])->name('subProducts');
        Route::get('subProductsedit/{id}', [SubProductController::class, 'subProductsedit'])->name('subProductsedit');
        Route::get('sub-product-create/{id}', [SubProductController::class, 'create'])->name('sub-product.create');
        Route::get('sub-productservice/{id}/edit', [SubProductController::class, 'edit'])->name('sub-product.edit');
        Route::get('sub-productservice/{id}/expenses', [SubProductController::class, 'expenses'])->name('sub-product.expenses');
        Route::post('sub-productservice/{id}/sent', [SubProductController::class, 'sent'])->name('sub-product.sent');
        Route::delete('sub-productservice/{id}', [SubProductController::class, 'destroy'])->name('sub-product.delete');
        Route::delete('sub-productservice/{id}/invoice', [SubProductController::class, 'destroyInvoice'])->name('sub-product.deleteinvoice');
        Route::delete('sub-productservice/{id}/bill', [SubProductController::class, 'destroyBill'])->name('sub-product.deleteBill');
        Route::put('sub-product-update/{id}', [SubProductController::class, 'update'])->name('sub-product.update');
        Route::post('sub-product-update-location/{id}', [SubProductController::class, 'updateLocation'])->name('sub-product.update-location');
        Route::put('sub-product-update.update/{id}', [SubProductController::class, 'sub_product_update'])->name('sub-product-update.update');
        Route::post('sub-product-store/{id}', [SubProductController::class, 'store'])->name('sub-product.store');


        Route::post('/save-products', [ProductServiceController::class, 'saveProducts'])->name('saveProducts');


        Route::get('brand/index', [BrandController::class, 'index'])->name('brand.index');
        Route::get('brand/create', [BrandController::class, 'create'])->name('brand.create');
        Route::get('brand/edit/{id}', [BrandController::class, 'edit'])->name('brand.edit');
        Route::put('brand/update/{id}', [BrandController::class, 'update'])->name('brand.update');
        Route::delete('brand/destroy/{id}', [BrandController::class, 'destroy'])->name('brand.destroy');
        Route::post('brand/store', [BrandController::class, 'store'])->name('brand.store');
        Route::get('import/brand/file', [BrandController::class, 'importFile'])->name('brand.file.import');
        Route::post('import/brand', [BrandController::class, 'import'])->name('brand.import');
        Route::get('brand/export', [BrandController::class, 'export'])->name('brand.export');
        Route::get('brand/update-by-id-form', [BrandController::class, 'updateByIdForm'])->name('brand.update-by-id-form');
        Route::post('brand/update-by-id', [BrandController::class, 'updateById'])->name('brand.update-by-id');
        Route::get('brand/get-by-id/{id}', [BrandController::class, 'getBrandById'])->name('brand.get-by-id');


        Route::get('sub-brand/index', [SubBrandController::class, 'index'])->name('sub-brand.index');
        Route::get('sub-brand/create', [SubBrandController::class, 'create'])->name('sub-brand.create');
        Route::get('sub-brand/edit/{id}', [SubBrandController::class, 'edit'])->name('sub-brand.edit');
        Route::put('sub-brand/update/{id}', [SubBrandController::class, 'update'])->name('sub-brand.update');
        Route::delete('sub-brand/destroy/{id}', [SubBrandController::class, 'destroy'])->name('sub-brand.destroy');
        Route::post('sub-brand/store', [SubBrandController::class, 'store'])->name('sub-brand.store');
        Route::get('sub-brand/export', [SubBrandController::class, 'export'])->name('sub-brand.export');
        Route::get('/api/brands/{categoryId}', [BrandController::class, 'getBrandByCategory'])->name('api.brands');
        Route::get('import/sub_brand/file', [SubBrandController::class, 'importFile'])->name('sub_brand.file.import');
        Route::post('import/sub_brand', [SubBrandController::class, 'import'])->name('sub_brand.import');

        Route::get('/api/addSubProducts/{bill_id}', [BillController::class, 'goToAddSubProducts'])->name('api.addSubProducts');
        Route::post('sub-productservice_bill/{id}/{bill_id}', [BillController::class, 'destroySubProduct'])->name('sub-product-bill.delete');
        Route::get('sub-product-create-bill/{id}', [BillController::class, 'createSubProduct'])->name('sub-product-bill.create');
        Route::post('sub-product-store-bill/{id}', [BillController::class, 'storeSubProduct'])->name('sub-product-bill.store');

        Route::post('sub-product-update-bill/{id}', [BillController::class, 'updateSubProduct'])->name('sub-product-bill.update');



        Route::delete('/invoice-expense/delete/{id}', [InvoiceController::class, 'destroyExpense'])->name('invoice-expense.destroy');

        Route::get('/invoice/addSubProducts/{invoice_id}', [InvoiceController::class, 'goToAddSubProducts'])->name('invoice.addSubProducts');
        Route::post('sub-productservice_invoice/{id}/{invoice_id}', [InvoiceController::class, 'destroySubProduct'])->name('sub-product-invoice.delete');
        Route::get('sub-product-create-invoice/{id}', [InvoiceController::class, 'createSubProduct'])->name('sub-product-invoice.create');
        Route::get('sub-product-create-invoice-expense/{id}', [InvoiceController::class, 'createSubProductExpense'])->name('sub-product-invoice.createExpense');
        Route::post('sub-product-store-invoice/{id}', [InvoiceController::class, 'storeSubProduct'])->name('sub-product-invoice.store');
        Route::post('/invoice-expense/store/{id}', [InvoiceController::class, 'storeInvoiceExpense'])->name('invoice-expense.store');

        Route::post('sub-product-update-invoice/{id}', [InvoiceController::class, 'updateSubProduct'])->name('sub-product-invoice.update');

        // Route::get('/get-sub-products/{productId}/{colorId?}/{colorIdIn?}/{country?}', [SubProductController::class, 'getSubProducts'])->name('get-sub-products');

        Route::get('/get-sub-products/{productId}', [SubProductController::class, 'getSubProducts'])->name('get-sub-products');
        Route::get('/get-sub-product-quantities-by-warehouse/{productId}', [SubProductController::class, 'getSubProductQuantitiesByWarehouse'])->name('get-sub-product-quantities-by-warehouse');
        Route::get('/get-item-category/{productId}', [SubProductController::class, 'getItemCategory'])->name('get-item-category');


        Route::get('/get-bills/{vendorId}', [BillController::class, 'getBills'])->name('get-bills');
        Route::get('/get-invoices/{vendorId}', [InvoiceController::class, 'getInvoices'])->name('get-invoices');
        Route::get('/get-bill-details/{bill_id}', [BillController::class, 'getBillDetails']);
        Route::get('/get-invoice-details/{invoice_id}', [InvoiceController::class, 'getInvoiceDetails']);

        Route::get('customerpayment/index', [CustomerPaymentController::class, 'index'])->name('customerpayment.index')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
        Route::get('customerrefund/index', [CustomerRefundController::class, 'index'])->name('customerrefund.index')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);

        Route::resource('customerpayment', CustomerPaymentController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
        Route::resource('customerrefund', CustomerRefundController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
        Route::get('get-currency-rate/{currencyId}/{invoiceId}', [CustomerRefundController::class, 'getCurrencyRate'])->name('get.currency.rate')->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
        Route::get('export/Gledger', [ReportController::class, 'Gledgerexport'])->name('Gledger.export');
        Route::get('export/ledger-summary', [ReportController::class, 'ledgerSummaryExport'])->name('ledger.summary.export');
        Route::get('export/attendance', [AttendanceEmployeeController::class, 'attendanceExport'])->name('attendance.export');
        Route::get('get_free_product/{id}', [SubProductController::class, 'GetFreeProduct'])->name('get_free_product');
        Route::get('get_free_product_in_warehouse/{W_id}/{id}', [SubProductController::class, 'GetFreeProduct_in_wareHouse'])->name('GetFreeProduct_in_wareHouse');


        Route::get('salik-account/index', [SalikAccountController::class, 'index'])->name('salik-account.index');
        Route::get('salik-account/create', [SalikAccountController::class, 'create'])->name('salik-account.create');
        Route::get('salik-account/edit/{id}', [SalikAccountController::class, 'edit'])->name('salik-account.edit');
        Route::put('salik-account/update/{id}', [SalikAccountController::class, 'update'])->name('salik-account.update');
        Route::delete('salik-account/destroy/{id}', [SalikAccountController::class, 'destroy'])->name('salik-account.destroy');
        Route::post('salik-account/store', [SalikAccountController::class, 'store'])->name('salik-account.store');


        Route::get('/get-exchange-rate/{currencyId}', [BillController::class, 'getExchangeRate'])->name('get-exchange-rate');

        Route::get('get-product-prices/{id}', [ProductServiceController::class, 'getProductPrices'])->name('get-product-prices');
        Route::get('/search', [SearchController::class, 'search'])->name('search');
        Route::get('/search/autocomplete', [SearchController::class, 'autocomplete'])->name('search.autocomplete');


        Route::post('/upload-file-customer', [CustomerController::class, 'uploadcustomer'])->name('upload.file.customer');
        Route::post('/delete-customer-file', [CustomerController::class, 'deleteFile'])->name('customer.file.destroy');

        Route::post('/upload-file-vendor', [VenderController::class, 'uploadvendor'])->name('upload.file.vendor');
        Route::delete('/delete-vendor-file', [VenderController::class, 'deleteFile'])->name('vendor.file.destroy');

        Route::post('/upload-file-bill', [BillController::class, 'uploadbill'])->name('upload.file.bill');
        Route::delete('/delete-bill-file', [BillController::class, 'deleteFile'])->name('bill.file.destroy');


        Route::post('/upload-file-invoice', [InvoiceController::class, 'uploadinvoice'])->name('upload.file.invoice');
        Route::post('/delete-invoice-file', [InvoiceController::class, 'deleteFile'])->name('invoice.file.destroy');

        Route::get('/fetch-brands', [BrandController::class, 'fetchBrands']);
        Route::get('/fetch-sub-brands', [ProductServiceController::class, 'fetchSubBrands']);
        Route::get('printpayment/{payment}', [CustomerPaymentController::class, 'printPayment'])->name('printpayment');
        Route::get('printvendorpayment/{payment}', [PaymentController::class, 'printvendorpayment'])->name('printvendorpayment');
        Route::get('sendpayment/{payment}', [PaymentController::class, 'sendpayment'])->name('sendpayment');
        Route::get('sendcustomerpayment/{payment}', [CustomerPaymentController::class, 'sendcustomerpayment'])->name('sendcustomerpayment');
        Route::get('printvendorrefundpayment/{payment}', [RefundController::class, 'printvendorrefundpayment'])->name('printvendorrefundpayment');
        Route::get('printrefundpayment/{payment}', [CustomerRefundController::class, 'printrefundpayment'])->name('printrefundpayment');


        Route::get('bill/{id}/delete', [BillController::class, 'showdelete'])->name('bill.showdelete');
        Route::get('bill/{id}/itemdelete/{qty}/{bill_id}', [BillController::class, 'showItemdelete'])->name('bill.showItemdelete');
        Route::get('invoice/{id}/delete', [InvoiceController::class, 'showdelete'])->name('invoice.showdelete');
        Route::get('invoice/{id}/itemdelete/{qty}/{inv_id}', [InvoiceController::class, 'showItemdelete'])->name('invoice.showItemdelete');


        Route::get('/countries', [CountryController::class, 'index'])->name('countries.index');
        Route::get('/countries/create', [CountryController::class, 'create'])->name('countries.create');
        Route::post('/countries', [CountryController::class, 'store'])->name('countries.store');
        Route::get('/countries/{id}/edit', [CountryController::class, 'edit'])->name('countries.edit');
        Route::put('/countries/{id}', [CountryController::class, 'update'])->name('countries.update');
        Route::delete('/countries/{id}', [CountryController::class, 'destroy'])->name('countries.destroy');



        Route::get('/colors', [ColorController::class, 'index'])->name('colors.index');
        Route::get('/colors/create', [ColorController::class, 'create'])->name('colors.create');
        Route::post('/colors', [ColorController::class, 'store'])->name('colors.store');
        Route::get('/colors/{id}', [ColorController::class, 'show'])->name('colors.show');
        Route::get('/colors/{id}/edit', [ColorController::class, 'edit'])->name('colors.edit');
        Route::put('/colors/{id}', [ColorController::class, 'update'])->name('colors.update');
        Route::delete('/colors/{id}', [ColorController::class, 'destroy'])->name('colors.destroy');

        Route::resource('manufacturers', ManufacturerController::class);
        Route::get('manufacturers/Tobill/{id}', [ManufacturerController::class, 'Tobill'])->name('manufacturers.Tobill');

        Route::get(
                'alternative-parts',
                [AltPartNumberController::class, 'partsIndex']
            )->name('alternative-parts.index');
 
        Route::prefix('sub-products')->group(function () {
            Route::get('{productNo}/alternatives', [AltPartNumberController::class, 'index'])
                ->name('sub-products.alternatives.index');

            Route::get('{productNo}/alternatives/create', [AltPartNumberController::class, 'create'])
                ->name('sub-products.alternatives.create');

            Route::post('{productNo}/alternatives', [AltPartNumberController::class, 'store'])
                ->name('sub-products.alternatives.store');

            Route::patch('alternatives/{id}', [AltPartNumberController::class, 'update'])
                ->name('sub-products.alternatives.update');

            Route::delete('alternatives/{id}', [AltPartNumberController::class, 'destroy'])
                ->name('sub-products.alternatives.destroy');
        });
        Route::get(
            'alternative-parts/import',
            [AltPartNumberController::class, 'importForm']
        )->name('alternatives.import.form');
        Route::get(
            'alternatives/import/template',
            [AltPartNumberController::class, 'downloadTemplate']
        )->name('alternatives.import.template');

        Route::post(
            'alternative-parts/import',
            [AltPartNumberController::class, 'import']
        )->name('alternatives.import');
       
         Route::prefix('price-rules')->group(function () {
            Route::get('template-form', [PriceListRoleController::class, 'templateForm'])->name('price-rules.form');
            Route::get('template-download', [PriceListRoleController::class, 'downloadTemplate'])->name('price-rules.download');
            Route::post('template-upload', [PriceListRoleController::class, 'uploadTemplate'])->name('price-rules.upload');
        });

        Route::resource('pricing-list-types', PricingListTypeController::class)->except(['show']);
        Route::resource('pricing-lists', PricingListsController::class)->except(['show']);
        
        Route::get('pricing-lists/export-template', [PricingListsController::class, 'export'])->name('pricing-lists.export');
        Route::post('pricing-lists/import', [PricingListsController::class, 'import'])->name('pricing-lists.import');



        Route::prefix('quotations')->group(function () {
            Route::get('/', [QuotationController::class, 'index'])->name('quotations.index');
            Route::get('/create', [QuotationController::class, 'create'])->name('quotations.create');
            Route::post('/store', [QuotationController::class, 'store'])->name('quotations.store');
            Route::get('/show/{id}', [QuotationController::class, 'show'])->name('quotations.show');
        });

        # Info edit
        Route::get('/quotations/{quotation}/edit', [QuotationController::class, 'edit'])
            ->name('quotations.edit');
        # Manual Edit 
        Route::get('/quotations/{quotation}/medit', [QuotationController::class, 'medit'])
            ->name('quotations.medit');
        
        # Info update
        Route::put('/quotations/{quotation}', [QuotationController::class, 'update'])
            ->name('quotations.update');

        Route::get('/quotations/{quotation}/quotation2saleorder',[QuotationController::class,'convert_to_sale_order_creaet'])->name("quotations.quotation2saleorder");
        
        
        # quotations items manual update
        Route::put('/quotations/{quotation}/quotations_part_manual_update', [QuotationController::class, 'quotations_part_manual_update'])
            ->name('quotations.quotations_part_manual_update');
        
        Route::get('/quotations/{quotation}/export', [QuotationController::class, 'export'])->name('quotations.export');
        Route::get('/quotations/createexport', [QuotationController::class, 'createexport'])->name('quotations.createexport');
        Route::get('/quotations/{quotation}/showexport', [QuotationController::class, 'showexport'])->name('quotations.showexport');
        Route::get(
            'quotations/{quotation}/export-pdf',
            [QuotationController::class, 'exportPdf']
        )->name('quotations.export.pdf');

        Route::post('/quotations/{quotation}/import', [QuotationController::class, 'import'])->name('quotations.import');

        Route::post(
            'quotations/{quotation}/convert_to_sale_order',
            [QuotationController::class, 'convert_to_sale_order']
        )->name('quotations.convert_to_sale_order');


    }
);
Route::get('/tracking-records', [TrackingController::class, 'index'])->name('tracking.index')->middleware(['auth']);
Route::get('/products-by-category', [ManufacturerController::class, 'getProductsByCategory'])->name('expense.product.create')->middleware(['auth']);
Route::get('stock_movements/export', [StockMovementController::class, 'export'])->name('stock_movements.export')->middleware(['auth']);
Route::resource('stock_movements', StockMovementController::class)->middleware(['auth']);
Route::get('rent_report', [InvoiceController::class, 'rent_report'])->name('rent_report')->middleware(['auth']);
Route::get('reports_rent_monthly', [InvoiceController::class, 'indexMonthly'])->name('reports.rent.monthly')->middleware(['auth']);
Route::get('import/bill/file', [BillController::class, 'importFile'])->name('bill.file.import')->middleware(['auth']);
Route::get('import/bill/sample', [BillController::class, 'downloadSample'])->name('bill.sample.download')->middleware(['auth']);
Route::post('import/bill', [BillController::class, 'import'])->name('bill.import')->middleware(['auth']);
Route::get('import/bill/review/{session_id}', [BillController::class, 'importReview'])->name('bill.import.review')->middleware(['auth']);
Route::post('import/bill/process/{session_id}', [BillController::class, 'processStagingImport'])->name('bill.import.process')->middleware(['auth']);
Route::get('/get-custom-fields', [BillController::class, 'getCustomFields'])->name('get-custom-fields')->middleware(['auth']);
Route::get('/get-custom-fields-inv', [InvoiceController::class, 'getCustomFields'])->name('get-custom-fields-inv')->middleware(['auth']);
Route::get('/bill/{id}/items', [BillController::class, 'fetchItems'])->middleware(['auth']);

// Resource route for basic CRUD
Route::get('warehouse-price-list', [WarehousePriceListController::class, 'index'])->name('warehouse-price-list.index')->middleware(['auth']);
Route::get('warehouse-price-list/create', [WarehousePriceListController::class, 'create'])->name('warehouse-price-list.create')->middleware(['auth']);
Route::post('warehouse-price-list', [WarehousePriceListController::class, 'store'])->name('warehouse-price-list.store')->middleware(['auth']);
Route::get('warehouse-price-list/{id}', [WarehousePriceListController::class, 'show'])->name('warehouse-price-list.show')->middleware(['auth']);
Route::get('warehouse-price-list/{id}/edit', [WarehousePriceListController::class, 'edit'])->name('warehouse-price-list.edit')->middleware(['auth']);
Route::put('warehouse-price-list/{id}', [WarehousePriceListController::class, 'update'])->name('warehouse-price-list.update')->middleware(['auth']);
Route::delete('warehouse-price-list/{id}', [WarehousePriceListController::class, 'destroy'])->name('warehouse-price-list.destroy')->middleware(['auth']);


// ComboOffer route for basic CRUD
Route::get('/combo-offers', [ComboOfferController::class, 'index'])->name('combo_offers.index')->middleware(['auth']);
Route::get('/combo-offers/create', [ComboOfferController::class, 'create'])->name('combo_offers.create')->middleware(['auth']);
Route::post('/combo-offers', [ComboOfferController::class, 'store'])->name('combo_offers.store')->middleware(['auth']);
Route::get('/combo-offers/{comboOffer}/edit', [ComboOfferController::class, 'edit'])->name('combo_offers.edit')->middleware(['auth']);
Route::put('/combo-offers/{comboOffer}', [ComboOfferController::class, 'update'])->name('combo_offers.update')->middleware(['auth']);
Route::delete('/combo-offers/{comboOffer}', [ComboOfferController::class, 'destroy'])->name('combo_offers.destroy')->middleware(['auth']);
Route::post('/combo-offers/check-multi-product', [ComboOfferController::class, 'checkMultiProduct'])->name('combo_offers.check-multi-product')->middleware(['auth']);
Route::get('/combo-offers/get-sub-brands', [ComboOfferController::class, 'getSubBrandsByWarehouse'])->name('combo_offers.get-sub-brands')->middleware(['auth']);
Route::get('/combo-offers/get-products', [ComboOfferController::class, 'getProductsForCombo'])->name('combo_offers.get-products')->middleware(['auth']);

// ComboOffer route for basic CRUD
Route::get('/vouchers', [VouchersController::class, 'index'])->name('vouchers.index')->middleware(['auth']);
Route::get('/vouchers/create', [VouchersController::class, 'create'])->name('vouchers.create')->middleware(['auth']);
Route::post('/vouchers', [VouchersController::class, 'store'])->name('vouchers.store')->middleware(['auth']);
Route::get('/vouchers/{id}/edit', [VouchersController::class, 'edit'])->name('vouchers.edit')->middleware(['auth']);
Route::put('/vouchers/{id}', [VouchersController::class, 'update'])->name('vouchers.update')->middleware(['auth']);
Route::delete('/vouchers/{id}', [VouchersController::class, 'destroy'])->name('vouchers.destroy')->middleware(['auth']);
Route::get('/vouchers/print/{id}', [VouchersController::class, 'print'])->name('vouchers.print')->middleware(['auth']);
Route::post('/vouchers/check', [VouchersController::class, 'check'])->name('vouchers.check')->middleware(['auth']);
Route::post('/vouchers/clear', [VouchersController::class, 'clear'])->name('vouchers.clear')->middleware(['auth']);


Route::prefix('pricelist')->name('pricelist.')->middleware(['auth'])->group(function () {
    Route::get('/', [PriceListRoleController::class, 'index'])->name('index');
    Route::get('/create', [PriceListRoleController::class, 'create'])->name('create');
    Route::post('/', [PriceListRoleController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [PriceListRoleController::class, 'edit'])->name('edit');
    Route::put('/{id}', [PriceListRoleController::class, 'update'])->name('update');
    Route::delete('/{id}', [PriceListRoleController::class, 'destroy'])->name('destroy');
    Route::get('/targets/{type}', [PriceListRoleController::class, 'getTargets'])->name('targets');
});

Route::get('payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index')->middleware(['auth']);
Route::get('payment-methods/create', [PaymentMethodController::class, 'create'])->name('payment-methods.create')->middleware(['auth']);
Route::post('payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store')->middleware(['auth']);
Route::get('payment-methods/{id}/edit', [PaymentMethodController::class, 'edit'])->name('payment-methods.edit')->middleware(['auth']);
Route::put('payment-methods/{id}', [PaymentMethodController::class, 'update'])->name('payment-methods.update')->middleware(['auth']);
Route::delete('payment-methods/{id}', [PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy')->middleware(['auth']);


Route::get('fetch-leads', [LeadController::class, 'fetchLeads'])->middleware(['auth']);
Route::post('/google-ads-lead', [GoogleAdsLeadController::class, 'store'])->middleware(['auth']);
Route::resource('lead_roles', LeadRoleController::class)->middleware(['auth']);
Route::delete('lead_roles/condition/{LeadRoleCondition}', [LeadRoleController::class, 'destroyCondition'])->name('lead_roles.condition.destroy')->middleware(['auth']);
Route::resource('campaigns', CampaignController::class)->middleware(['auth']);
// web.php
Route::post('/leads/{lead}/update-stage', [LeadController::class, 'updateStage'])->middleware(['auth']);
Route::post('/leads/assign', [LeadController::class, 'assign'])->name('leads.assign')->middleware(['auth']);
Route::post('/leads/bulk-delete', [LeadController::class, 'bulkDelete'])->name('leads.bulkDelete')->middleware(['auth']);
Route::post('/leads/assign-stage', [LeadController::class, 'assignStage'])->name('leads.assignStage')->middleware(['auth']);
Route::post('/leads/assign-source', [LeadController::class, 'assignSource'])->name('leads.assignSource')->middleware(['auth']);
Route::get('/client-deals/{id}', [ClientController::class, 'client_deals'])->name('client.deals')->middleware(['auth']);

Route::get('/leads/stage/{stage}', function (\App\Models\LeadStage $stage) {
    $leads = $stage->lead()->paginate(10);
    $html = '';
    foreach ($leads as $lead) {
        $html .= view('leads._lead_cards', compact('lead'))->render();
    }
    return response()->json([
        'html' => $html,
        'next_page_url' => $leads->nextPageUrl()
    ]);
})->name('leads.stage.more')->middleware(['auth']);

Route::get('/currency', [CurrencyController::class, 'index'])->name('currency.index')->middleware(['auth']);
Route::get('/currency/create', [CurrencyController::class, 'create'])->name('currency.create')->middleware(['auth']);
Route::post('/currency', [CurrencyController::class, 'store'])->name('currency.store')->middleware(['auth']);
Route::get('/currency/{id}/edit', [CurrencyController::class, 'edit'])->name('currency.edit')->middleware(['auth']);
Route::put('/currency/{id}', [CurrencyController::class, 'update'])->name('currency.update')->middleware(['auth']);
Route::delete('/currency/{id}', [CurrencyController::class, 'destroy'])->name('currency.destroy')->middleware(['auth']);
Route::get('/average-rate/{currency_id}', [PaymentController::class, 'getAverageRate'])->middleware(['auth']);
Route::get('/currencies/{id}/rate', function ($id) {
    $currency = \App\Models\Currency::find($id);
    return response()->json([
        'rate' => $currency?->exchange_rate, // or any column you use for rate
    ]);
})->middleware(['auth']);



Route::post('/bills/{bill}/calculate-average-rate', [BillController::class, 'calculateAverageRate'])->middleware(['auth']);
Route::get('journal/showdelete/{id}', [JournalEntryController::class, 'showdelete'])->name('journal-entry.showdelete')->middleware(['auth']);
Route::get('payment/{payment}/allocate-form', [PaymentController::class, 'allocateForm'])->name('payment.allocate.form')->middleware(['auth']);
Route::post('payment/{payment}/allocate', [PaymentController::class, 'storeAllocation'])->name('payment.allocate.store')->middleware(['auth']);
Route::get('bill/ledger/{id}', [BillController::class, 'bill_ledger'])->name('bill.ledger')->middleware(['auth', XSS::class]);
Route::get('customerpayment/{payment}/allocate-form', [CustomerPaymentController::class, 'allocateForm'])->name('customerpayment.allocate.form')->middleware(['auth']);
Route::post('customerpayment/{payment}/allocate', [CustomerPaymentController::class, 'storeAllocation'])->name('customerpayment.allocate.store')->middleware(['auth']);
Route::post('/invoices/{invoice}/calculate-average-rate', [InvoiceController::class, 'calculateAverageRate'])->middleware(['auth']);
Route::get('/accounts/{account}/products', [SubProductController::class, 'productsByAccount'])->name('accounts.products')->middleware(['auth']);
Route::post('/accounts/{account}/add-stock-movements', [SubProductController::class, 'addStockMovementsForAccount'])->name('accounts.add-stock-movements')->middleware(['auth']);
// routes/web.php
Route::get('/convert-currency', [PaymentController::class, 'convert'])->name('currency.convert')->middleware(['auth']);
Route::get('/convert-currencyAED', [PaymentController::class, 'convertAED'])->name('currency.convertAED')->middleware(['auth']);
Route::get('/deals/{deal}/products/{product}/edit1', [DealController::class, 'editProduct'])->name('deals.products.edit1')->middleware(['auth']);
Route::put('/deals/{deal}/products/{product}', [DealController::class, 'updateProduct'])->name('deals.products.update1')->middleware(['auth']);

Route::get('car_accessories/search', [CarManufacturerController::class, 'search'])->name('car_accessories.search')->middleware(['auth']);
Route::post('car_accessories/search', [CarManufacturerController::class, 'doSearch'])->name('car_accessories.doSearch')->middleware(['auth']);
Route::post('car_accessories/create-request', [CarManufacturerController::class, 'createRequest'])->name('car_accessories.createRequest')->middleware(['auth']);
Route::post('car_accessories/store-request', [CarManufacturerController::class, 'storeRequest'])->name('car_accessories.storeRequest')->middleware(['auth']);
Route::post('car_accessories/items/{item}/hold', [CarManufacturerController::class, 'holdItem'])->name('car_accessories.items.hold')->middleware(['auth']);
Route::post('car_accessories/items/{item}/unhold', [CarManufacturerController::class, 'unholdItem'])->name('car_accessories.items.unhold')->middleware(['auth']);
Route::delete('car_accessories/items/{item}/delete', [CarManufacturerController::class, 'deleteItem'])->name('car_accessories.items.delete')->middleware(['auth']);
Route::post('car_accessories/{car_accessory}/assign', [CarManufacturerController::class, 'assignStock'])->name('car_accessories.assign')->middleware(['auth']);
Route::post('car_accessories/{car_accessory}/assign-with-date', [CarManufacturerController::class, 'assignStockWithDate'])->name('car_accessories.assignWithDate')->middleware(['auth']);
Route::post('car_accessories/link-cars-with-accessory', [CarManufacturerController::class, 'linkCarsWithAccessory'])->name('car_accessories.linkCarsWithAccessory')->middleware(['auth']);
Route::get('car_accessories/clear-session', [CarManufacturerController::class, 'clearSession'])->name('car_accessories.clearSession')->middleware(['auth']);
Route::post('car_accessories/save-list', [CarManufacturerController::class, 'saveList'])->name('car_accessories.saveList')->middleware(['auth']);
Route::post('car_accessories/create-request-from-list', [CarManufacturerController::class, 'createRequestFromList'])->name('car_accessories.createRequestFromList')->middleware(['auth']);

// Direct Expenses
use App\Http\Controllers\DirectExpenseController;
use App\Http\Controllers\DirectExpensePaymentController;
use App\Http\Controllers\ProController;
Route::get('direct-expenses/search', [DirectExpenseController::class, 'search'])->name('direct_expenses.search')->middleware(['auth']);
Route::post('direct-expenses/search', [DirectExpenseController::class, 'doSearch'])->name('direct_expenses.doSearch')->middleware(['auth']);
Route::post('direct-expenses/store', [DirectExpenseController::class, 'store'])->name('direct_expenses.store')->middleware(['auth']);
Route::get('direct-expenses', [DirectExpenseController::class, 'index'])->name('direct_expenses.index')->middleware(['auth']);
Route::get('direct-expenses/{directExpense}', [DirectExpenseController::class, 'show'])->name('direct_expenses.show')->middleware(['auth']);
Route::get('direct-expenses/{directExpense}/edit', [DirectExpenseController::class, 'edit'])->name('direct_expenses.edit')->middleware(['auth']);
Route::get('direct-expenses/ledger/{id}', [DirectExpenseController::class, 'ledger'])->name('direct_expenses.ledger')->middleware(['auth', XSS::class]);
Route::put('direct-expenses/{directExpense}', [DirectExpenseController::class, 'update'])->name('direct_expenses.update')->middleware(['auth']);
Route::delete('direct-expenses/{id}', [DirectExpenseController::class, 'destroy'])->name('direct_expenses.destroy')->middleware(['auth']);
Route::delete('direct-expenses/item/{itemId}', [DirectExpenseController::class, 'destroyItem'])->name('direct_expenses.destroy_item')->middleware(['auth']);

// Direct Expense Payments
Route::get('direct-expense-payments', [DirectExpensePaymentController::class, 'index'])->name('direct_expense_payments.index')->middleware(['auth']);
Route::get('direct-expenses/{directExpense}/payments/create', [DirectExpensePaymentController::class, 'create'])->name('direct_expense_payments.create')->middleware(['auth']);
Route::post('direct-expenses/{directExpense}/payments', [DirectExpensePaymentController::class, 'store'])->name('direct_expense_payments.store')->middleware(['auth']);
Route::get('direct-expense-payments/{payment}', [DirectExpensePaymentController::class, 'show'])->name('direct_expense_payments.show')->middleware(['auth']);
Route::get('direct-expense-payments/{payment}/edit', [DirectExpensePaymentController::class, 'edit'])->name('direct_expense_payments.edit')->middleware(['auth']);
Route::put('direct-expense-payments/{payment}', [DirectExpensePaymentController::class, 'update'])->name('direct_expense_payments.update')->middleware(['auth']);
Route::post('direct-expense-payments/{payment}/send', [DirectExpensePaymentController::class, 'sendPayment'])->name('direct_expense_payments.send')->middleware(['auth']);
Route::delete('direct-expense-payments/{payment}', [DirectExpensePaymentController::class, 'destroy'])->name('direct_expense_payments.destroy')->middleware(['auth']);

// PRO (Purchase Request Order)
Route::resource('pro', ProController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
Route::get('import/pro/file', [ProController::class, 'importFile'])->name('pro.file.import')->middleware(['auth']);
Route::get('import/pro/file/create-subproducts', [ProController::class, 'importFileCreateSubProducts'])->name('pro.file.import.create-subproducts')->middleware(['auth']);
Route::get('import/pro/file/items-only', [ProController::class, 'importFileItemsOnly'])->name('pro.file.import.items-only')->middleware(['auth']);
Route::get('import/pro/sample', [ProController::class, 'downloadSample'])->name('pro.sample.download')->middleware(['auth']);
Route::get('import/pro/sample/create-sub', [ProController::class, 'downloadSampleCreateSub'])->name('pro.sample.create-sub.download')->middleware(['auth']);
Route::get('import/pro/sample/items-only', [ProController::class, 'downloadSampleItemsOnly'])->name('pro.sample.items-only.download')->middleware(['auth']);
Route::post('import/pro', [ProController::class, 'import'])->name('pro.import')->middleware(['auth']);
Route::get('import/pro/create-subproducts', function () {
    return redirect()->route('pro.index');
})->middleware(['auth']);
Route::post('import/pro/create-subproducts', [ProController::class, 'importCreateSubProducts'])->name('pro.import.create-subproducts')->middleware(['auth']);
Route::post('import/pro/items-only', [ProController::class, 'importItemsOnly'])->name('pro.import.items-only')->middleware(['auth']);
Route::get('export/pro', [ProController::class, 'export'])->name('pro.export')->middleware(['auth']);
Route::get('export/pro', [MasterlistLedgerController::class, 'export'])->name('pro.recourds')->middleware(['auth']);

// ASN (Advanced Shipment Notice)
use App\Http\Controllers\AsnController;
use App\Http\Controllers\GrnController;
Route::resource('asn', AsnController::class)->middleware(['auth', XSS::class, RevalidateBackHistory::class]);
Route::get('import/asn/file', [AsnController::class, 'importFile'])->name('asn.file.import')->middleware(['auth']);
Route::get('import/asn/file/items-only', [AsnController::class, 'importFileItemsOnly'])->name('asn.file.import.items-only')->middleware(['auth']);
Route::get('import/asn/sample', [AsnController::class, 'downloadSample'])->name('asn.sample.download')->middleware(['auth']);
Route::get('import/asn/sample/items-only', [AsnController::class, 'downloadSampleItemsOnly'])->name('asn.sample.items-only.download')->middleware(['auth']);
Route::post('import/asn', [AsnController::class, 'import'])->name('asn.import')->middleware(['auth']);
Route::post('import/asn/items-only', [AsnController::class, 'importItemsOnly'])->name('asn.import.items-only')->middleware(['auth']);
Route::get('import/asn/errors-report', [AsnController::class, 'downloadImportErrorsReport'])->name('asn.import.download-errors')->middleware(['auth']);
Route::get('asn/{asn}/grn', [AsnController::class, 'grn'])->name('asn.grn')->middleware(['auth']);
Route::post('asn/{asn}/grn', [AsnController::class, 'grnStore'])->name('asn.grn.store')->middleware(['auth']);
Route::post('asn/{asn}/create-grn', [AsnController::class, 'createGrn'])->name('asn.create-grn')->middleware(['auth']);
Route::post('asn/{asn}/convert-to-bill', [AsnController::class, 'convertToBill'])->name('asn.convert-to-bill')->middleware(['auth']);
Route::match(['get', 'post'], 'asn/{asn}/convert-to-inventory', [AsnController::class, 'convertToInventory'])->name('asn.convert-to-inventory')->middleware(['auth']);
Route::post('asn/{asn}/convert-selected-to-bill', [AsnController::class, 'convertSelectedItemsToBill'])->name('asn.convert-selected-to-bill')->middleware(['auth']);
Route::post('asn/{asn}/item/{item}/reverse-inventory', [AsnController::class, 'reverseInventoryItem'])->name('asn.item.reverse-inventory')->middleware(['auth']);
Route::get('asn/{asn}/item/{item}/barcode', [AsnController::class, 'printItemBarcode'])->name('asn.item.barcode')->middleware(['auth']);
Route::get('asn/export', [AsnController::class, 'export'])->name('asn.export')->middleware(['auth', XSS::class]);

// GRN Routes
Route::get('grn', [GrnController::class, 'index'])->name('grn.index')->middleware(['auth']);
Route::get('grn/{id}', [GrnController::class, 'show'])->name('grn.show')->middleware(['auth']);
Route::put('grn/{id}', [GrnController::class, 'update'])->name('grn.update')->middleware(['auth']);
Route::post('grn/{id}/convert-to-bill', [GrnController::class, 'convertToBill'])->name('grn.convert-to-bill')->middleware(['auth']);
Route::get('asn/{asn}/export', [AsnController::class, 'export'])->name('asn.single.export')->middleware(['auth', XSS::class]);
Route::post('car_accessories/clear-saved-list', [CarManufacturerController::class, 'clearSavedList'])->name('car_accessories.clearSavedList')->middleware(['auth']);
Route::resource('car_accessories', CarManufacturerController ::class)->where(['car_accessory' => '[0-9]+'])->middleware(['auth']);


// web.php
// Route::get('/notifications/{id}', [NotificationController::class, 'show'])->name('notifications.show');
Route::get('/notifications', [NotificationController::class, 'index_web'])->name('notifications.index_web')->middleware(['auth']);

Route::get('pos_refund',[PosProductsRefundController::class , 'index'])->name('pos_product_refund.index')->middleware(['auth']);
Route::get('pos_refund/create',[PosProductsRefundController::class , 'create'])->name('pos_product_refund.create')->middleware(['auth']);
Route::post('pos_refund/products',[PosProductsRefundController::class, 'get_products_to_refund'])->name('pos_product_refund.get_products_to_refund')->middleware(['auth']);
Route::post('pos_refund/products/store',[PosProductsRefundController::class, 'store_products_refund'])->name('pos_product_refund.store_products_refund')->middleware(['auth']);
Route::get('pos_refund/refund/{posId}/info',[PosProductsRefundController::class, 'refundableItems'])->name('pos_product_refund.refundableItems')->middleware(['auth']);
Route::get('pos_refund/print/{id}',[PosProductsRefundController::class, 'print'])->name('pos_product_refund.print')->middleware(['auth']);
Route::get('pos_refund/ledger/{id}',[PosProductsRefundController::class, 'refund_ledger'])->name('pos_product_refund.ledger')->middleware(['auth', XSS::class]);
Route::get('pos/ledger/{id}', [PosController::class, 'pos_ledger'])->name('pos.ledger')->middleware(['auth', XSS::class]);
Route::get('bank-account/export', [BankAccountController::class, 'export'])
    ->name('bank-account.export')->middleware(['auth']);



