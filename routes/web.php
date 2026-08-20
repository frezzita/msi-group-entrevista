<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\EstadoController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\ReservaController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/registro', [RegisterController::class, 'create'])->name('register');
    Route::post('/registro', [RegisterController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', fn () => redirect()->route('reservas.index'));

    // Punto 1: ABM de mesas
    Route::get('/mesas', [MesaController::class, 'index'])->name('mesas.index');
    Route::post('/mesas', [MesaController::class, 'store'])->name('mesas.store');
    Route::get('/mesas/{mesa}/editar', [MesaController::class, 'edit'])->name('mesas.edit');
    Route::put('/mesas/{mesa}', [MesaController::class, 'update'])->name('mesas.update');
    Route::delete('/mesas/{mesa}', [MesaController::class, 'destroy'])->name('mesas.destroy');

    // Puntos 3 y 4: alta de reserva y listado por fecha
    Route::get('/reservas', [ReservaController::class, 'index'])->name('reservas.index');
    Route::get('/reservas/nueva', [ReservaController::class, 'create'])->name('reservas.create');
    Route::post('/reservas', [ReservaController::class, 'store'])->name('reservas.store');
    Route::delete('/reservas/{reserva}', [ReservaController::class, 'destroy'])->name('reservas.destroy');

    Route::get('/estado', [EstadoController::class, 'index'])->name('estado.index');

    // Mismos datos en JSON: el listado del punto 4 como endpoint, y los dos que
    // consumen las pantallas (horarios validos del dia y ocupacion en vivo).
    Route::get('/api/reservas', [ReservaController::class, 'indexJson'])->name('api.reservas');
    Route::get('/api/horarios', [ReservaController::class, 'horarios'])->name('api.horarios');
    Route::get('/api/estado', [EstadoController::class, 'json'])->name('api.estado');
});
