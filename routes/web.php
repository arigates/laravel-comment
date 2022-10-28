<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'loginView'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.submit');

Route::get('/logout', [AuthController::class, 'logout'])
    ->name('logout');

Route::prefix('/posts')->name('post.')->middleware('auth')->group(function () {
    Route::get('/', [PostController::class, 'index'])
        ->name('index');

    Route::get('/create', [PostController::class, 'create'])
        ->name('create');

    Route::post('/store', [PostController::class, 'store'])
        ->name('store');

    Route::get('/show/{slug}', [PostController::class, 'show'])
        ->name('show');

    Route::get('/edit/{post}', [PostController::class, 'edit'])
        ->name('edit');

    Route::post('/update/{post}', [PostController::class, 'update'])
        ->name('update');

    Route::delete('/{post}', [PostController::class, 'delete'])
        ->name('delete');

    Route::delete('/{post}/attachment/{attachment}', [PostController::class, 'deleteAttachment'])
        ->name('delete.attachment');

    Route::get('/{post}/comments', [PostController::class, 'comment'])
        ->name('comment');

    Route::post('/{post}/comments', [PostController::class, 'addComment'])
        ->name('comment.submit');

    Route::delete('/comments/{comment}', [PostController::class, 'deleteComment'])
        ->name('comment.delete');
});
