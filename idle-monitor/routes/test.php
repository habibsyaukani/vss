<?php
use Illuminate\Support\Facades\Route;

Route::get('/test-419', function () {
    throw new \Illuminate\Session\TokenMismatchException();
});
