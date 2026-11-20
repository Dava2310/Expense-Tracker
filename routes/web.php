<?php

use App\Http\Controllers\Userzone\AccountController;
use App\Http\Controllers\Userzone\CategoryController;
use App\Http\Controllers\Userzone\CurrencyController;
use App\Http\Controllers\Userzone\DashboardController;
use App\Http\Controllers\Userzone\ExpenseController;
use App\Http\Controllers\Userzone\IncomeController;
use App\Http\Controllers\Userzone\ProfileController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

/*
 * Public Website routes
 */
Route::get('/', WelcomeController::class)->name('welcome');

/*
 * Authentication routes
 */
require __DIR__.'/auth.php';

/*
 * Userzone routes
 */
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('currencies', CurrencyController::class);
    Route::resource('accounts', AccountController::class);
    Route::resource('incomes', IncomeController::class)->except(['show', 'destroy']);
    Route::resource('expenses', ExpenseController::class)->except(['show', 'destroy']);
    Route::resource('categories', CategoryController::class)->except('show');

    // User's profile management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
