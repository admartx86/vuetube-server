<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\VideoController;

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

Route::get('/', function () {
    return view('welcome');
});
Route::resource('articles', ArticleController::class);
Route::post('/register', [RegistrationController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LogoutController::class, 'logout']);
Route::get('/videos/{id}', [VideoController::class, 'show']);
Route::get('/videos', [VideoController::class, 'show_all']);
Route::post('/videos', [VideoController::class, 'upload']);
Route::delete('/videos/{id}', [VideoController::class, 'delete']);
Route::patch('/videos/{unique_code}/description', [VideoController::class, 'updateDescription']);
