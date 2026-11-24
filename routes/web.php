<?php

use Arrocy\Phonebook\Http\Controllers\PhonebookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->prefix('phonebook')->name('phonebook.')->group(function (): void {
    Route::get('/', [PhonebookController::class, 'index'])->name('index');
    Route::post('/', [PhonebookController::class, 'store'])->name('store');
    Route::post('/import', [PhonebookController::class, 'import'])->name('import');
    Route::get('/export', [PhonebookController::class, 'export'])->name('export');
    Route::get('/{phonebook}/edit', [PhonebookController::class, 'edit'])->name('edit');
    Route::put('/{phonebook}', [PhonebookController::class, 'update'])->name('update');
    Route::get('/{phonebook}', [PhonebookController::class, 'toggle'])->name('toggle');
    Route::delete('/{phonebook}', [PhonebookController::class, 'destroy'])->name('destroy');
    Route::delete('/', [PhonebookController::class, 'bulkDelete'])->name('bulk-destroy');
});