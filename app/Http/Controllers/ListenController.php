<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Listen;
use App\Models\ABook;

class ListenController extends Controller
{
    // Обновление или создание прогресса
    public function update(Request $request)
    {
        $request->validate([
            'a_book_id' => 'required|exists:a_books,id',
            'a_chapter_id' => 'required|exists:a_chapters,id',
            'position' => 'required|integer|min:0',
        ]);

        $user = $request->user();

        $listen = Listen::updateOrCreate(
            [
                'user_id' => $user->id,
                'a_book_id' => $request->a_book_id,
                'a_chapter_id' => $request->a_chapter_id,
            ],
            [
                'position' => $request->position,
            ]
        );

        return response()->json(['status' => 'ok']);
    }

    // Получение текущей позиции главы
    public function get(Request $request)
    {
        $request->validate([
            'a_book_id' => 'required|exists:a_books,id',
            'a_chapter_id' => 'required|exists:a_chapters,id',
        ]);

        $user = $request->user();

        $listen = Listen::where('user_id', $user->id)
            ->where('a_book_id', $request->a_book_id)
            ->where('a_chapter_id', $request->a_chapter_id)
            ->first();

        return response()->json(['position' => $listen?->position ?? 0]);
    }

    // Новый метод: получение списка прослушанных книг пользователя
    public function listenedBooks(Request $request)
    {
        $user = $request->user();

        // Получаем уникальные ID книг, в которых позиция > 0 (прослушаны)
        $listenedBookIds = Listen::where('user_id', $user->id)
            ->where('position', '>', 0)
            ->distinct()
            ->pluck('a_book_id');

        // Загружаем книги с авторами
        $books = ABook::with('author')
            ->whereIn('id', $listenedBookIds)
            ->get()
            ->map(function ($book) {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author?->name ?? 'Неизвестен',
                    'cover_url' => $book->cover_url ? url('/storage/' . $book->cover_url) : null,
                ];
            });

        return response()->json($books);
    }
}
