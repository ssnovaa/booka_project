<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ABookController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FavoriteApiController;
use App\Http\Controllers\Api\UserApiController; // <-- добавлено для кабинета
use App\Http\Controllers\ListenController; // <-- добавлено для прослушанных книг

// ===== API авторизация =====
Route::post('/login', [AuthController::class, 'login']);

// Каталог аудиокниг с фильтрами
Route::get('/abooks', [ABookController::class, 'apiIndex']);

// Список жанров для фильтров
Route::get('/genres', [GenreController::class, 'apiIndex']);

// Список авторов для фильтров
Route::get('/authors', [AuthorController::class, 'apiIndex']);

// Страница книги и глав
Route::get('/abooks/{id}', [ABookController::class, 'apiShow']);
Route::get('/abooks/{id}/chapters', [ABookController::class, 'apiChapters']);

// ===== API для профиля пользователя и избранного =====
Route::middleware('auth:sanctum')->group(function () {
    // Личный кабинет пользователя (профиль)
    Route::get('/profile', [UserApiController::class, 'profile']); // <-- добавлено

    // Избранное
    Route::get('/favorites', [FavoriteApiController::class, 'index']);
    Route::post('/favorites/{id}', [FavoriteApiController::class, 'store']);
    Route::delete('/favorites/{id}', [FavoriteApiController::class, 'destroy']);

    // Прослушанные книги пользователя
    Route::get('/listened-books', [ListenController::class, 'listenedBooks']); // <-- добавлено
});
