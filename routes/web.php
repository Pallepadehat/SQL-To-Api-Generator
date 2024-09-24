<?php

use Illuminate\Support\Facades\Route;

// routes/web.php
use App\Http\Controllers\SQLGenerationController;

Route::get('/', function () {
    return view('upload_sql');
});

Route::post('/generate-sql', [SQLGenerationController::class, 'generateSQL']);
Route::post('/generate-code', [SQLGenerationController::class, 'generateCode']);
