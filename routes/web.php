<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/register', [UsuarioController::class, 'create'])->name('usuarios.create');
Route::post('/register', [UsuarioController::class, 'store'])->name('usuarios.store');
Route::get('/consult', [UsuarioController::class, 'showConsultForm'])->name('usuarios.showConsultForm');
Route::post('/consult', [UsuarioController::class, 'consult'])->name('usuarios.consult');
