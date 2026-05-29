<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['status' => 'Dental Practi-Smart API running']);
});
