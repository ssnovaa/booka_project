<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ABookController;
use App\Http\Controllers\AudioStreamController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ListenController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\Admin\ReaderController;
use App\Http\Controllers\Admin\ChapterController;
use App\Http\Controllers\Admin\ABookImportController;
use App\Http\Controllers\Admin\SeriesController;
use App\Http\Controllers\SeriesPublicController; // <-- добавлено!
use App\Http\Middleware\IsAdmin;
use App\Models\ABook;

// 🏠 Главная страница — показывает свежие книги и жанры
Route::get('/', function () {
    $books = ABook::latest()->take(16)->get();
    $genres = \App\Models\Genre::withCount('books')->orderBy('name')->get();

    return view('welcome', [
        'books' => $books,
        'genres' => $genres,
        'user' => Auth::user(),
    ]);
});

// 📚 Публичный каталог аудиокниг
Route::get('/abooks', [ABookController::class, 'index'])->name('abooks.index');
Route::get('/abooks/{id}', [ABookController::class, 'show'])->name('abooks.show');

// 📂 Жанры — страница списка жанров
Route::get('/genres', [GenreController::class, 'index'])->name('genres.index');

// 📖 Публичная страница серии — все книги серии
Route::get('/series/{id}', [SeriesPublicController::class, 'show'])->name('series.show');

// 🔐 Админка (только для авторизованных админов)
Route::middleware(['auth', IsAdmin::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Админ-панель
        Route::get('/', fn () => view('admin.dashboard'))->name('dashboard');

        // Управление книгами
        Route::get('/abooks', [ABookController::class, 'index'])->name('abooks.index');
        Route::get('/abooks/create', [ABookController::class, 'create'])->name('abooks.create');
        Route::post('/abooks', [ABookController::class, 'store'])->name('abooks.store');
        Route::get('/abooks/{id}/edit', [ABookController::class, 'edit'])->name('abooks.edit');
        Route::put('/abooks/{id}', [ABookController::class, 'update'])->name('abooks.update');
        Route::delete('/abooks/{id}', [ABookController::class, 'destroy'])->name('abooks.destroy');

        // Импорт книг из FTP (через кнопку)
        Route::post('/abooks/import', [ABookImportController::class, 'import'])->name('abooks.import');

        // Управление жанрами (CRUD кроме show)
        Route::resource('genres', GenreController::class)->except(['show']);

        // --- Series: CRUD для серий книг ---
        Route::resource('series', SeriesController::class)->except(['show']);

        // Управление чтецами (Readers) — полный CRUD
        Route::resource('readers', ReaderController::class);

        // Управление главами аудиокниг (CRUD)
        Route::prefix('abooks/{book}/chapters')->name('chapters.')->group(function () {
            Route::get('/create', [ChapterController::class, 'create'])->name('create');
            Route::post('/', [ChapterController::class, 'store'])->name('store');
            Route::get('/{chapter}/edit', [ChapterController::class, 'edit'])->name('edit');
            Route::put('/{chapter}', [ChapterController::class, 'update'])->name('update');
            Route::delete('/{chapter}', [ChapterController::class, 'destroy'])->name('destroy');
        });
    });

// 🔊 Потоковое аудио (демо-глава — всем, остальные главы — только авторизованным, проверка идёт в контроллере!)
Route::get('/audio/{id}', [AudioStreamController::class, 'stream'])
    ->name('audio.stream');

// 🔐 Авторизация и регистрация (Laravel Breeze)
require __DIR__.'/auth.php';

// ❤️ Избранное (только для пользователей)
Route::middleware('auth')->group(function () {
    Route::post('/abooks/{id}/favorite', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
});

// 🎧 Прогресс прослушивания (для авторизованных)
Route::middleware('auth')->group(function () {
    Route::post('/listen/update', [ListenController::class, 'update'])->name('listen.update');
    Route::get('/listen', [ListenController::class, 'get'])->name('listen.get');
});

// Тестовый API-маршрут для диагностики работы роутинга web.php
Route::get('/api/debug-web', function () {
    return response()->json(['from' => 'web.php']);
});
