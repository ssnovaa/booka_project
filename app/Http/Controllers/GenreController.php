<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    /**
     * Отобразить список жанров (админка).
     */
    public function index()
    {
        // Получаем все жанры, сортируем по имени, и считаем количество книг в каждом жанре
        $genres = Genre::withCount('books')->orderBy('name')->get();

        // Возвращаем представление с данными
        return view('admin.genres.index', compact('genres'));
    }

    /**
     * API: Отдать список жанров в формате JSON для Flutter/внешнего клиента.
     * GET /api/genres
     */
    public function apiIndex()
    {
        $genres = Genre::orderBy('name')->get(['id', 'name']);
        return response()->json($genres, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Показать форму создания нового жанра.
     */
    public function create()
    {
        return view('admin.genres.create');
    }

    /**
     * Сохранить новый жанр в базе данных.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:genres,name',
        ]);

        Genre::create([
            'name' => $validated['name'],
        ]);

        return redirect()->route('admin.genres.index')->with('success', 'Жанр добавлен');
    }

    /**
     * Показать форму редактирования жанра.
     */
    public function edit(Genre $genre)
    {
        return view('admin.genres.edit', compact('genre'));
    }

    /**
     * Обновить жанр в базе данных.
     */
    public function update(Request $request, Genre $genre)
    {
        $validated = $request->validate([
            // Уникальность с исключением текущего жанра по id
            'name' => 'required|string|max:255|unique:genres,name,' . $genre->id,
        ]);

        $genre->update([
            'name' => $validated['name'],
        ]);

        return redirect()->route('admin.genres.index')->with('success', 'Жанр обновлён');
    }

    /**
     * Удалить жанр из базы данных.
     */
    public function destroy(Genre $genre)
    {
        $genre->delete();

        return redirect()->route('admin.genres.index')->with('success', 'Жанр удалён');
    }
}
