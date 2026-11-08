<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\Seller;
use App\Http\Controllers\StoreController;
use App\Livewire\CatalogSearch;
use App\Livewire\Seller\ListingForm;
use App\Livewire\Seller\ManageOrder;
use App\Livewire\ShowCart;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vitrine (pública)
|--------------------------------------------------------------------------
| Route::livewire() aponta a URL direto para um componente Livewire de
| página inteira; Route::get() aponta para um método de controller.
*/
Route::livewire('/', CatalogSearch::class)->name('home');
Route::get('anuncios/{listing}', [ListingController::class, 'show'])->name('listings.show');
// {seller:slug} = busca a loja pela coluna slug em vez do id: /lojas/pneus-do-ze
Route::get('lojas/{seller:slug}', [StoreController::class, 'show'])->name('stores.show');

// O carrinho funciona sem login; a conta só é exigida para finalizar.
Route::livewire('carrinho', ShowCart::class)->name('cart');
Route::post('carrinho/itens', [CartController::class, 'store'])->middleware('throttle:cart')->name('cart.store');

/*
|--------------------------------------------------------------------------
| Webhook do gateway de pagamento (sem login, protegido por assinatura)
|--------------------------------------------------------------------------
*/
Route::post('webhooks/pagamentos', PaymentWebhookController::class)
    ->middleware('throttle:webhooks')
    ->name('webhooks.payments');

/*
|--------------------------------------------------------------------------
| Área logada (e-mail verificado)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Comprador
    Route::get('compras/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
    Route::post('compras/{purchase}/cancelar', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
    Route::post('compras/{purchase}/simular-pagamento', [PurchaseController::class, 'simulatePayment'])->name('purchases.simulate-payment');

    Route::get('pedidos', [OrderController::class, 'index'])->name('orders.index');
    Route::get('pedidos/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Cadastro de loja
    Route::get('vender', [Seller\OnboardingController::class, 'create'])->name('seller.onboarding.create');
    Route::post('vender', [Seller\OnboardingController::class, 'store'])->name('seller.onboarding.store');

    // Painel do vendedor: o middleware "seller" exige que o usuário tenha loja.
    Route::prefix('painel')->name('seller.')->middleware('seller')->group(function () {
        Route::get('/', [Seller\DashboardController::class, 'show'])->name('dashboard');
        Route::put('loja', [Seller\DashboardController::class, 'update'])->name('settings.update');

        Route::get('anuncios', [Seller\ListingController::class, 'index'])->name('listings.index');
        Route::livewire('anuncios/novo', ListingForm::class)->name('listings.create');
        Route::livewire('anuncios/{listing}/editar', ListingForm::class)->name('listings.edit');

        Route::get('locais', [Seller\LocationController::class, 'index'])->name('locations.index');
        Route::get('locais/novo', [Seller\LocationController::class, 'create'])->name('locations.create');
        Route::post('locais', [Seller\LocationController::class, 'store'])->name('locations.store');
        Route::get('locais/{location}/editar', [Seller\LocationController::class, 'edit'])->name('locations.edit');
        Route::put('locais/{location}', [Seller\LocationController::class, 'update'])->name('locations.update');

        Route::get('pedidos', [Seller\OrderController::class, 'index'])->name('orders.index');
        Route::livewire('pedidos/{order}', ManageOrder::class)->name('orders.show');
    });
});

require __DIR__.'/settings.php';
