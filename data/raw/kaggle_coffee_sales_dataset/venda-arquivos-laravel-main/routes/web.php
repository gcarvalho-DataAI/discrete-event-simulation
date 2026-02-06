<?php

use App\Http\Controllers\AccountAuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminAdsController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminPaymentsController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MercadoPagoController;
use App\Http\Controllers\MercadoPagoWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/anuncio/{ad}', [HomeController::class, 'show'])->name('ad.show');

Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

Route::middleware('customer')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'submit'])->name('checkout.submit');
});
Route::get('/checkout/sucesso', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/pendente', [CheckoutController::class, 'pending'])->name('checkout.pending');
Route::get('/checkout/falha', [CheckoutController::class, 'failure'])->name('checkout.failure');

Route::prefix('conta')->group(function () {
    Route::get('/login', [AccountAuthController::class, 'showLogin'])->name('account.login');
    Route::post('/login', [AccountAuthController::class, 'login'])->name('account.login.submit');
    Route::get('/registrar', [AccountAuthController::class, 'showRegister'])->name('account.register');
    Route::post('/registrar', [AccountAuthController::class, 'register'])->name('account.register.submit');
    Route::post('/logout', [AccountAuthController::class, 'logout'])->name('account.logout');

    Route::middleware('customer')->group(function () {
        Route::get('/', [AccountController::class, 'dashboard'])->name('account.dashboard');
        Route::get('/pedidos', [AccountController::class, 'orders'])->name('account.orders');
        Route::get('/pedidos/{id}', [AccountController::class, 'orderDetail'])->name('account.orders.detail');
        Route::get('/pedidos/{id}/nota', [AccountController::class, 'receipt'])->name('account.orders.receipt');
        Route::get('/perfil', [AccountController::class, 'profile'])->name('account.profile');
        Route::post('/perfil', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    });
});

Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    Route::get('/mfa', [AdminAuthController::class, 'showMfa'])->name('admin.mfa');
    Route::post('/mfa', [AdminAuthController::class, 'verifyMfa'])->name('admin.mfa.verify');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::middleware('admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/mfa/setup', [AdminController::class, 'mfaSetup'])->name('admin.mfa.setup');
        Route::post('/mfa/enable', [AdminController::class, 'enableMfa'])->name('admin.mfa.enable');
        Route::post('/mfa/disable', [AdminController::class, 'disableMfa'])->name('admin.mfa.disable');

        Route::get('/anuncios/ativos', [AdminAdsController::class, 'active'])->name('admin.ads.active');
        Route::get('/anuncios/pausados', [AdminAdsController::class, 'paused'])->name('admin.ads.paused');
        Route::get('/anuncios/novo', [AdminAdsController::class, 'create'])->name('admin.ads.create');
        Route::post('/anuncios', [AdminAdsController::class, 'store'])->name('admin.ads.store');
        Route::get('/anuncios/{ad}/editar', [AdminAdsController::class, 'edit'])->name('admin.ads.edit');
        Route::post('/anuncios/{ad}', [AdminAdsController::class, 'update'])->name('admin.ads.update');
        Route::post('/anuncios/{ad}/toggle', [AdminAdsController::class, 'toggle'])->name('admin.ads.toggle');
        Route::post('/anuncios/{ad}/delete', [AdminAdsController::class, 'destroy'])->name('admin.ads.delete');

        Route::get('/pagamentos', [AdminPaymentsController::class, 'index'])->name('admin.payments');
        Route::post('/pagamentos', [AdminPaymentsController::class, 'updateSettings'])->name('admin.payments.update');
        Route::post('/pagamentos/hooks', [AdminPaymentsController::class, 'storeHook'])->name('admin.payments.hooks.store');
        Route::post('/pagamentos/hooks/{hook}/delete', [AdminPaymentsController::class, 'deleteHook'])->name('admin.payments.hooks.delete');
        Route::post('/pagamentos/hooks/{hook}/test', [AdminPaymentsController::class, 'testHook'])->name('admin.payments.hooks.test');
    });
});

Route::prefix('api')->group(function () {
    Route::post('/mercadopago/preference', [MercadoPagoController::class, 'createPreference']);
    Route::post('/mercadopago/pix', [MercadoPagoController::class, 'createPix']);
    Route::get('/mercadopago/public-key', [MercadoPagoController::class, 'publicKey']);
    Route::post('/mercadopago/webhook', [MercadoPagoWebhookController::class, 'handle']);
});
