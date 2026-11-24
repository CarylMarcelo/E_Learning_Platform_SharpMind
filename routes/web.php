<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

// Redirect root to login or dashboard
Route::get('/', function () {
    return redirect()->route('login');
});

// Public Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected System Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('members', MemberController::class);
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('levels', LevelController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::resource('transactions', TransactionController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('transactions/{transaction}/approve', [TransactionController::class, 'approve'])->name('transactions.approve');
    Route::post('transactions/{transaction}/cancel', [TransactionController::class, 'cancel'])->name('transactions.cancel');
    Route::get('transactions/{transaction}/pay', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('transactions/{transaction}/pay', [PaymentController::class, 'store'])->name('payments.store');

    Route::get('/weather', [WeatherController::class, 'show'])->name('weather.show');
});
