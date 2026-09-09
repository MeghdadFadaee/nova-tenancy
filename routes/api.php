<?php

use Illuminate\Support\Facades\Route;
use MeghdadFadaee\NovaTenancy\Http\Controllers\CurrentTenantController;
use MeghdadFadaee\NovaTenancy\Http\Controllers\CurrentTenantLogoController;
use MeghdadFadaee\NovaTenancy\Http\Controllers\TenantIndexController;

Route::get('/tenants', TenantIndexController::class)->name('tenants.index');
Route::get('/current', [CurrentTenantController::class, 'show'])->name('current.show');
Route::post('/current', [CurrentTenantController::class, 'store'])->name('current.store');
Route::delete('/current', [CurrentTenantController::class, 'destroy'])->name('current.destroy');
Route::get('/current/logo', CurrentTenantLogoController::class)->name('current.logo');
