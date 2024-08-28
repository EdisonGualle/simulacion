<?php

use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/reset-password', [ResetPasswordController::class, 'loadResetPasswordView']);
Route::post('/reset-password', [ResetPasswordController::class, 'updatePassword'])->name('reset-password');

Route::get('/', function () {
    return view('welcome');
});