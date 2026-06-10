<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $role = auth()->user()->role;
    if ($role === 'GA') {
        return redirect()->route('ga.dashboard');
    } elseif ($role === 'SECURITY') {
        return redirect()->route('security.dashboard');
    } elseif ($role === 'MANAGEMENT') {
        return redirect()->route('management.dashboard');
    }
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/inbound/create', function() {
        return view('inbound.create');
    })->name('inbound.create');

    Route::get('/inbound/edit/{id}', function($id) {
        return view('inbound.create', ['id' => $id]);
    })->name('inbound.edit');

    Route::get('/outbound/create', function() {
        return view('outbound.create');
    })->name('outbound.create');

    Route::get('/outbound/edit/{id}', function($id) {
        return view('outbound.create', ['id' => $id]);
    })->name('outbound.edit');

    Route::get('/ga/dashboard', function() {
        if(auth()->user()->role !== 'GA') {
            abort(403);
        }
        return view('ga.dashboard');
    })->name('ga.dashboard');

    Route::get('/ga/history', function() {
        if(auth()->user()->role !== 'GA') {
            abort(403);
        }
        return view('ga.history');
    })->name('ga.history');

    Route::get('/management/dashboard', function() {
        if(auth()->user()->role !== 'MANAGEMENT') {
            abort(403);
        }
        return view('management.dashboard');
    })->name('management.dashboard');

    Route::get('/security/dashboard', function() {
        if(auth()->user()->role !== 'SECURITY') {
            abort(403);
        }
        return view('security.dashboard');
    })->name('security.dashboard');

    Route::get('/security/history', function() {
        if(auth()->user()->role !== 'SECURITY') {
            abort(403);
        }
        return view('security.history');
    })->name('security.history');
});

require __DIR__.'/auth.php';
