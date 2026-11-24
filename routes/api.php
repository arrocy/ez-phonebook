<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['api'])->prefix('api/phonebook')->name('phonebook.api.')->group(function (): void {
    // Add package API routes here.
});