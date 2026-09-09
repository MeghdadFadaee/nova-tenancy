<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('NovaTenancy'))->name('nova-tenancy.index');
