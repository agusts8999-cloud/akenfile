<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\FolderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('/upload', [FileController::class, 'upload']);
    Route::get('/files', [FileController::class, 'index']);
    Route::get('/download/{file}', [FileController::class, 'download']);
    Route::delete('/file/{file}', [FileController::class, 'destroy']);

    Route::post('/folder', [FolderController::class, 'store']);
    Route::delete('/folder/{folder}', [FolderController::class, 'destroy']);
});
