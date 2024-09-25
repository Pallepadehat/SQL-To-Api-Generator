<?php

use Illuminate\Support\Facades\Route;

// routes/web.php
use App\Http\Controllers\GenerateCodeController;

Route::get('/', function () {
    return view('upload_sql');
});

Route::post('/generate-sql', [GenerateCodeController::class, 'generateSql']);
Route::post('/generate-code', [GenerateCodeController::class, 'generateCode']);
