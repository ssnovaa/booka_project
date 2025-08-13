@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6">Редактировать книгу: {{ $book->title }}</h1>

    @php
        $selectedGenres = $book->genres->pluck('id')->toArray();
    @endphp

    <form action="{{ route('admin.abooks.update', $book->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block mb-1 font-semibold">Название:</label>
            <input type="text" name="title" value="{{ old('title', $book->title) }}" required class="w-full border p-2 rounded">
        </div>

        <div>
            <label class="block mb-1 font-semibold">Автор:</label>
            <input type="text" name="author" value="{{ old('author', $book->author->name ?? '') }}" required class="w-full border p-2 rounded">
        </div>

        {{-- 📚 Серия книги --}}
        <div>
            <label class="block mb-1 font-semibold">Серия:</label>
            <select name="series_id" class="w-full border p-2 rounded">
                <option value="">Без серии</option>
                @foreach(\App\Models\Series::orderBy('title')->get() as $series)
                    <option value="{{ $series->id }}"
                        @if(old('series_id', $book->series_id) == $series->id) selected @endif>
                        {{ $series->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-semibold">Описание:</label>
            <textarea name="description" rows="4" class="w-full border p-2 rounded">{{ old('description', $book->description) }}</textarea>
        </div>

        <div>
            <label class="block mb-1 font-semibold">Текущая обложка:</label>
            @if($book->cover_url)
                <img src="{{ asset('storage/' . $book->cover_url) }}" alt="Обложка" class="w-32 mb-2 rounded">
            @else
                <p>Обложка отсутствует</p>
            @endif
        </div>

        <div>
            <label class="block mb-1 font-semibold">Заменить обложку:</label>
            <input type="file" name="cover_file" accept="image/*" class="w-full border p-2 rounded">
            <p class="text-sm text-gray-500 mt-1">Если не хотите менять — оставьте пустым.</p>
            @error('cover_file')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block mb-1 font-semibold">Жанры:</label>
            <div class="flex flex-wrap gap-4">
                @foreach($genres as $genre)
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="genres[]" value="{{ $genre->id }}"
                            {{ in_array($genre->id, $selectedGenres, true) ? 'checked' : '' }}
                            class="mr-2">
                        {{ $genre->name }}
                    </label>
                @endforeach
            </div>
            @error('genres')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block mb-1 font-semibold">Длительность (в минутах):</label>
        </div>

        <div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                💾 Сохранить изменения
            </button>
            <a href="{{ route('admin.abooks.index') }}" class="ml-4 text-gray-600 hover:underline">Отмена</a>
        </div>
    </form>

    {{-- === Управление главами книги === --}}
    <hr class="my-8">

    <h2 class="text-xl font-bold mb-4">Главы книги</h2>
    <a href="{{ route('admin.chapters.create', ['book' => $book->id]) }}"
       class="mb-4 inline-block bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
        ➕ Добавить главу
    </a>

    @if($book->chapters->count())
        <table class="w-full border-collapse border border-gray-300">
            <thead>
                <tr>
                    <th class="border px-3 py-2">#</th>
                    <th class="border px-3 py-2">Название главы</th>
                    <th class="border px-3 py-2">Аудиофайл</th>
                    <th class="border px-3 py-2">Порядок</th>
                    <th class="border px-3 py-2">Действия</th>
                </tr>
            </thead>
            <tbody>
                @foreach($book->chapters as $chapter)
                    <tr>
                        <td class="border px-3 py-2">{{ $chapter->order }}</td>
                        <td class="border px-3 py-2">{{ $chapter->title }}</td>
                        <td class="border px-3 py-2">
                            @if($chapter->audio_path)
                                <a href="{{ route('audio.stream', $chapter->id) }}" target="_blank" class="text-blue-600 underline">Слушать</a>
                            @else
                                <span class="text-gray-400">Нет файла</span>
                            @endif
                        </td>
                        <td class="border px-3 py-2">{{ $chapter->order }}</td>
                        <td class="border px-3 py-2">
                            <a href="{{ route('admin.chapters.edit', [$book->id, $chapter->id]) }}" class="text-blue-600 hover:underline mr-2">✏️</a>
                            <form action="{{ route('admin.chapters.destroy', [$book->id, $chapter->id]) }}" method="POST" class="inline" onsubmit="return confirm('Удалить главу?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">🗑️</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>У этой книги пока нет глав.</p>
    @endif

</div>
@endsection
