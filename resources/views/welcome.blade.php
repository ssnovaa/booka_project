@extends('layouts.app')

@section('content')
<div class="bg-white min-h-screen py-8">
    <div class="container mx-auto px-4 flex flex-col lg:flex-row gap-8">

        {{-- 📚 Контентная часть слева --}}
        <div class="w-full lg:w-3/4 space-y-12">
            {{-- 🔝 Верхняя навигация --}}
            <div class="flex items-center justify-between border-b pb-4">
                <div class="flex items-center gap-4 text-lg font-semibold text-gray-800">
                    <span class="border-b-2 border-blue-600 pb-1">Новинки</span>
                    <a href="#" class="hover:text-blue-600">Лента</a>
                    <a href="#" class="hover:text-blue-600">Рекомендации</a>
                </div>
                <div class="flex items-center gap-2">
                    <button class="hover:bg-gray-100 p-2 rounded">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- 🧱 Сетка карточек с реальными книгами --}}
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
                @forelse($books as $book)
                    @include('partials.book_card', ['book' => $book])
                @empty
                    <p class="text-gray-500">Книги не найдены.</p>
                @endforelse
            </div>
        </div>

        {{-- 🎯 Правая колонка: жанры и комментарии --}}
        <aside class="w-full lg:w-1/4 space-y-8">
            {{-- Жанры --}}
            <div>
                <h3 class="text-lg font-semibold mb-2">Жанры</h3>
                @if($genres->count())
                    <ul class="text-sm text-gray-700 space-y-1">
                        @foreach($genres as $genre)
                            <li>
                                <a href="{{ route('abooks.index', ['genre' => $genre->id]) }}" class="flex justify-between hover:text-blue-600">
                                    {{ $genre->name }}
                                    <span class="text-gray-400">{{ $genre->books_count }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-500">Жанры отсутствуют</p>
                @endif
                <a href="{{ route('genres.index') }}" class="mt-2 inline-block text-sm text-blue-600 hover:underline">Все жанры →</a>
            </div>

            {{-- Последние комментарии (заглушки) --}}
            <div>
                <h3 class="text-lg font-semibold mb-2">Последние комментарии</h3>
                <ul class="text-sm text-gray-700 space-y-2">
                    <li>
                        <strong class="text-blue-600">Светлана Мальцева</strong><br>
                        <span class="text-gray-500">Идеальный мир для Лекаря</span><br>
                        <span class="text-gray-600">Не смогла дослушать...</span>
                    </li>
                    <li>
                        <strong class="text-blue-600">Аудиофан</strong><br>
                        <span class="text-gray-500">Сердце зверя</span><br>
                        <span class="text-gray-600">Очень зацепило. Спасибо!</span>
                    </li>
                </ul>
            </div>
        </aside>
    </div>
</div>
@endsection
