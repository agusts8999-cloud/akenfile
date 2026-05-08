<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ControlCenterController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\PublicShareController;
use App\Http\Controllers\SharedController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [FileController::class, 'dashboard'])->name('dashboard');
    Route::get('/files', [FileController::class, 'index'])->name('files.index');
    Route::post('/upload', [FileController::class, 'upload'])->name('files.upload');
    Route::get('/download/{file}', [FileController::class, 'download'])->name('files.download');
    Route::get('/file/{file}/preview', [FileController::class, 'preview'])->name('files.preview');
    Route::get('/file/{file}/preview/image', [FileController::class, 'previewImage'])->name('files.preview.image');
    Route::get('/file/{file}/content', [FileController::class, 'content'])->name('files.content');
    Route::patch('/file/{file}/content', [FileController::class, 'updateContent'])->name('files.content.update');
    Route::delete('/file/{file}', [FileController::class, 'destroy'])->name('files.destroy');
    Route::patch('/file/{file}/rename', [FileController::class, 'rename'])->name('files.rename');
    Route::patch('/file/{file}/move', [FileController::class, 'move'])->name('files.move');
    Route::post('/files/bulk/delete', [FileController::class, 'bulkDelete'])->name('files.bulk.delete');
    Route::post('/files/bulk/copy', [FileController::class, 'bulkCopy'])->name('files.bulk.copy');
    Route::post('/files/bulk/move', [FileController::class, 'bulkMove'])->name('files.bulk.move');

    Route::post('/folder', [FolderController::class, 'store'])->name('folders.store');
    Route::delete('/folder/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');
    Route::patch('/folder/{folder}/rename', [FolderController::class, 'rename'])->name('folders.rename');

    Route::resource('users', UserController::class)->except(['show']);

    Route::get('/shared', [SharedController::class, 'index'])->name('shared.index');
    Route::post('/shared/user', [SharedController::class, 'shareToUser'])->name('shared.user.store');
    Route::delete('/shared/user/{share}', [SharedController::class, 'revokeShare'])->name('shared.user.destroy');
    Route::post('/shared/public-link', [SharedController::class, 'createPublicLink'])->name('shared.public-link.store');
    Route::delete('/shared/public-link/{link}', [SharedController::class, 'revokePublicLink'])->name('shared.public-link.destroy');
    Route::post('/shared/public-link/{link}/send-email', [SharedController::class, 'sendPublicLinkEmail'])->name('shared.public-link.send-email');

    Route::get('/trash', [TrashController::class, 'index'])->name('trash.index');
    Route::post('/trash/file/{fileId}/restore', [TrashController::class, 'restoreFile'])->name('trash.files.restore');
    Route::post('/trash/folder/{folderId}/restore', [TrashController::class, 'restoreFolder'])->name('trash.folders.restore');
    Route::delete('/trash/file/{fileId}/force', [TrashController::class, 'forceDeleteFile'])->name('trash.files.force');
    Route::delete('/trash/folder/{folderId}/force', [TrashController::class, 'forceDeleteFolder'])->name('trash.folders.force');
    Route::post('/trash/bulk/restore', [TrashController::class, 'bulkRestore'])->name('trash.bulk.restore');
    Route::delete('/trash/bulk/force', [TrashController::class, 'bulkForceDelete'])->name('trash.bulk.force');

    Route::get('/control-center', [ControlCenterController::class, 'index'])->name('control-center.index');
    Route::post('/control-center/settings', [ControlCenterController::class, 'updateSettings'])->name('control-center.settings.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/s/{token}', [PublicShareController::class, 'download'])->name('public-share.download');

require __DIR__.'/auth.php';
