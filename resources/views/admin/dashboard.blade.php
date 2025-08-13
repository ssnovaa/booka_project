{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6">Админ-панель</h1>

        {{-- Панель действий --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            {{-- Книги --}}
            <a href="{{ route('admin.abooks.index') }}"
               class="block p-6 border rounded hover:bg-gray-100 transition">
                <h2 class="text-xl font-semibold mb-2">📘 Управление книгами</h2>
                <p class="text-sm text-gray-600">Просмотр, добавление и удаление аудиокниг.</p>
            </a>

            {{-- Жанры --}}
            <a href="{{ route('admin.genres.index') }}"
               class="block p-6 border rounded hover:bg-gray-100 transition">
                <h2 class="text-xl font-semibold mb-2">🗂 Управление жанрами</h2>
                <p class="text-sm text-gray-600">Просмотр, добавление и удаление жанров.</p>
            </a>

            {{-- Чтецы --}}
            <a href="{{ route('admin.readers.index') }}" 
                class="block p-6 border rounded hover:bg-gray-100 transition">
                <h2 class="text-xl font-semibold mb-2">🎙️ Управление чтецами</h2>
                <p class="text-sm text-gray-600">Просмотр, добавление и удаление чтецов.</p>
            </a>

            {{-- Серии --}}
            <a href="{{ route('admin.series.index') }}"
                class="block p-6 border rounded hover:bg-gray-100 transition">
                <h2 class="text-xl font-semibold mb-2">📚 Управление сериями</h2>
                <p class="text-sm text-gray-600">Просмотр, добавление и удаление серий книг.</p>
            </a>

        </div>
    </div>
@endsection
