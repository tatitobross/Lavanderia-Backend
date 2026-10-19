<?php

use App\Http\Controllers\{AuthController, LaundryController};
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('user', [AuthController::class, 'user']);
    Route::put('profile', [AuthController::class, 'updateProfile']);
    Route::put('password', [AuthController::class, 'changePassword']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::middleware('role:Administrador,Operador')->group(function () {
        // El operador sólo consulta los catálogos para crear y atender órdenes.
        Route::get('customers', [LaundryController::class, 'customers']);
        Route::get('customers/{customer}', [LaundryController::class, 'showCustomer']);
        Route::get('garment-types', [LaundryController::class, 'garmentTypes']);
        Route::get('garment-types/{garmentType}', [LaundryController::class, 'showGarmentType']);
        Route::get('services', [LaundryController::class, 'services']);
        Route::get('services/{service}', [LaundryController::class, 'showService']);
        Route::get('orders', [LaundryController::class, 'orders']);
        Route::get('orders/{order}', [LaundryController::class, 'showOrder']);
        Route::post('orders', [LaundryController::class, 'storeOrder']);
        Route::put('orders/{order}', [LaundryController::class, 'updateOrder']);
        Route::post('orders/{order}/advance', [LaundryController::class, 'advanceOrder']);
    });

    Route::middleware('role:Administrador')->group(function () {
        Route::get('dashboard', [LaundryController::class, 'dashboard']);
        Route::post('customers', [LaundryController::class, 'storeCustomer']);
        Route::put('customers/{customer}', [LaundryController::class, 'updateCustomer']);
        Route::delete('customers/{customer}', [LaundryController::class, 'destroyCustomer']);
        Route::post('garment-types', [LaundryController::class, 'storeGarmentType']);
        Route::put('garment-types/{garmentType}', [LaundryController::class, 'updateGarmentType']);
        Route::delete('garment-types/{garmentType}', [LaundryController::class, 'destroyGarmentType']);
        Route::post('services', [LaundryController::class, 'storeService']);
        Route::put('services/{service}', [LaundryController::class, 'updateService']);
        Route::delete('services/{service}', [LaundryController::class, 'destroyService']);
        Route::delete('orders/{order}', [LaundryController::class, 'destroyOrder']);
    });
});
