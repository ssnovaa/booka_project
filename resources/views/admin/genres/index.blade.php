@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6">Жанры</h1>

    @auth
        @if(auth()->user()->is_admin)
            @if(session('success'))
                <div class="mb-4 text-green-600">{{ session('success') }}</div>
            @endif

            <div class="mb-4">
                <a href="{{ route('admin.genres.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    ➕ Добавить жанр
                </a>
            </div>

            <table class="w-full table-auto border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border px-4 py-2 text-left">Название</th>
                        <th class="border px-4 py-2 w-48">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($genres as $genre)
                        <tr>
                            <td class="border px-4 py-2">{{ $genre->name }}</td>
                            <td class="border px-4 py-2">
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.genres.edit', $genre) }}" class="text-blue-600 hover:underline">Изменить</a>
                                    <form action="{{ route('admin.genres.destroy', $genre) }}" method="POST" onsubmit="return confirm('Удалить жанр?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Удалить</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-4 text-center text-gray-500">Жанры отсутствуют</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @else
            {{-- Для обычных пользователей --}}
            @if($genres->count())
                <ul class="text-sm text-gray-700 space-y-1">
                    @foreach($genres as $genre)
                        <li>
                            <a href="{{ route('abooks.index', ['genre' => $genre->id]) }}" class="flex justify-between hover:text-blue-600">
                                {{ $genre->name }}
                                <span class="text-gray-400">{{ $genre->books_count ?? 0 }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-gray-500">Жанры отсутствуют</p>
            @endif
        @endif
    @else
        {{-- Для гостей показываем то же, что и для обычных пользователей --}}
        @if($genres->count())
            <ul class="text-sm text-gray-700 space-y-1">
                @foreach($genres as $genre)
                    <li>
                        <a href="{{ route('abooks.index', ['genre' => $genre->id]) }}" class="flex justify-between hover:text-blue-600">
                            {{ $genre->name }}
                            <span class="text-gray-400">{{ $genre->books_count ?? 0 }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-gray-500">Жанры отсутствуют</p>
        @endif
    @endauth
</div>
@endsection
