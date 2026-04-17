<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return view('application');
})->name('login');

Route::get('{any?}', function () {
    return view('application');
})->where('any', '.*');
