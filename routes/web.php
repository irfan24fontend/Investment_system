<?php
use App\Http\Controllers\Admin\{AdminController, OrderController};
use App\Http\Controllers\{AuthController, InvestorController};
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('/login', 'auth.login')->name('login')->middleware('guest');
Route::view('/register', 'auth.register')->name('register')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware(['guest', 'throttle:6,1']);
Route::post('/register', [AuthController::class, 'register'])->middleware(['guest', 'throttle:5,10']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [InvestorController::class, 'dashboard'])->name('dashboard');
    Route::get('/notices', [InvestorController::class, 'notices'])->name('notices');
    Route::view('/help', 'help')->name('help');

    Route::middleware('role:investor')->group(function () {
        Route::get('/offers', [InvestorController::class, 'offers'])->name('offers');
        Route::get('/offers/{offer}', [InvestorController::class, 'offer'])->name('offers.show');
        Route::post('/offers/{offer}/invest', [InvestorController::class, 'invest'])->middleware('throttle:10,1')->name('offers.invest');
        Route::get('/investments', [InvestorController::class, 'investments'])->name('investments');
        Route::get('/investments/{investment}', [InvestorController::class, 'investment'])->name('investments.show');
        Route::get('/investments/{investment}/receipt', [InvestorController::class, 'receipt'])->name('investments.receipt');
        Route::get('/wallet', [InvestorController::class, 'wallet'])->name('wallet');
        Route::post('/wallet/deposit/cash', [InvestorController::class, 'depositCash'])->middleware('throttle:10,1')->name('deposit.cash');
        Route::post('/wallet/deposit/bkash', [InvestorController::class, 'depositBkash'])->middleware('throttle:10,1')->name('deposit.bkash');
        Route::post('/wallet/deposit/bank', [InvestorController::class, 'depositBank'])->middleware('throttle:10,1')->name('deposit.bank');
        Route::post('/wallet/withdraw', [InvestorController::class, 'withdraw'])->middleware('throttle:5,1')->name('withdraw');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('role:finance_manager,order_manager')->get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::middleware('role:finance_manager')->group(function () {
            Route::get('/investors', [AdminController::class, 'investors'])->name('investors');
            Route::post('/investors/{user}/verify', [AdminController::class, 'verifyInvestor'])->name('investors.verify');
            Route::get('/deposits', [AdminController::class, 'deposits'])->name('deposits');
            Route::get('/deposits/{deposit}/receipt', [AdminController::class, 'depositReceipt'])->name('deposits.receipt');
            Route::post('/deposits/{deposit}/approve', [AdminController::class, 'depositApprove'])->name('deposits.approve');
            Route::post('/deposits/{deposit}/reject', [AdminController::class, 'depositReject'])->name('deposits.reject');
            Route::get('/withdrawals', [AdminController::class, 'withdrawals'])->name('withdrawals');
            Route::post('/withdrawals/{w}/complete', [AdminController::class, 'withdrawalComplete'])->name('withdrawals.complete');
            Route::post('/withdrawals/{w}/reject', [AdminController::class, 'withdrawalReject'])->name('withdrawals.reject');
            Route::get('/settlements', [AdminController::class, 'settlements'])->name('settlements');
            Route::post('/settlements/prepare/{investment}', [AdminController::class, 'settlementPrepare'])->name('settlements.prepare');
            Route::post('/settlements/{settlement}/approve', [AdminController::class, 'settlementApprove'])->name('settlements.approve');
            Route::post('/settlements/{settlement}/recalculate', [AdminController::class, 'settlementRecalculate'])->name('settlements.recalculate');
            Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
            Route::get('/reports/ledger.csv', [AdminController::class, 'exportLedger'])->name('reports.ledger');
        });
        Route::middleware('role:order_manager')->group(function () {
            Route::get('/orders', [OrderController::class, 'index'])->name('orders');
            Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
            Route::post('/orders/{order}/status', [OrderController::class, 'status'])->name('orders.status');
        });
        Route::middleware('role:super_admin')->group(function () { // super admin only
            Route::get('/offers', [AdminController::class, 'offers'])->name('offers');
            Route::post('/offers', [AdminController::class, 'offerStore'])->name('offers.store');
            Route::get('/offers/{offer}/edit', [AdminController::class, 'offerEdit'])->name('offers.edit');
            Route::post('/offers/{offer}/update', [AdminController::class, 'offerUpdate'])->name('offers.update');
            Route::get('/offers/{offer}/progress', [AdminController::class, 'offerProgress'])->name('offers.progress');
            Route::post('/offers/{offer}/progress', [AdminController::class, 'offerProgressStore'])->name('offers.progress.store');
            Route::post('/offers/{offer}/complete', [AdminController::class, 'offerComplete'])->name('offers.complete');
            Route::post('/offers/{offer}/status', [AdminController::class, 'offerStatus'])->name('offers.status');
            Route::get('/audit', [AdminController::class, 'auditLogs'])->name('audit');
        });
    });
});
