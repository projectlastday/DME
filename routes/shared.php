<?php

use App\Http\Controllers\Shared\PrivateNoteImageController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->get('/note-images/{noteImage}', [PrivateNoteImageController::class, 'show'])
    ->name('note-images.show');
