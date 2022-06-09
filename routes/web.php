<?php

use Illuminate\Support\Facades\Route;

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



Auth::routes();


Route::group(['middleware' => 'auth'], function (){
    Route::get('/', [\App\Http\Controllers\ChatController::class, 'index']);
    Route::get('/chat', [App\Http\Controllers\ChatController::class, 'index'])->name('chat');
    Route::post('/open/chat', [App\Http\Controllers\ChatController::class, 'openChat']);
    Route::get('/get/users',[App\Http\Controllers\ChatController::class, 'getUsers']);
    Route::post('/send/message',[App\Http\Controllers\ChatController::class, 'sendMessage']);
});
