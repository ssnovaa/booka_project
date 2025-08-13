{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ config('app.name', 'Booka') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('logo-booka.png') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/alpinejs" defer></script>
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">

        {{-- ЧЁРНАЯ ПОЛОСА С ЛОГОТИПОМ + КНОПКИ + ИЗБРАННОЕ + АВТОРИЗАЦИЯ --}}
        <header class="bg-[#0D1117] text-white py-4 shadow-md">
            <div class="mx-auto max-w-[1200px] flex items-center justify-between px-4">

                {{-- ЛЕВАЯ ЧАСТЬ: Логотип + Главная + Админка + поиск, селекторы --}}
                <div class="flex items-center gap-6">

                    {{-- Логотип --}}
                    <a href="{{ url('/') }}" class="flex items-center gap-3 hover:opacity-80 transition" aria-label="Перейти на главную">
                        <img src="{{ asset('logo-booka.png') }}" alt="Booka" class="h-8 w-8 rounded-full shadow" />
                        <span class="text-xl font-bold text-[#22D3EE]">Booka</span>
                    </a>

                    {{-- Кнопка Главная --}}
                    <a href="{{ url('/') }}"
                       class="text-sm font-medium hover:text-cyan-400 transition px-3 py-1 rounded border border-transparent hover:border-cyan-400">
                        Главная
                    </a>

                    {{-- Кнопка Админка (только для админов) --}}
                    @auth
                        @if(auth()->user()?->is_admin)
                            <a href="{{ route('admin.dashboard') }}"
                               class="text-sm font-medium hover:text-cyan-400 transition px-3 py-1 rounded border border-transparent hover:border-cyan-400">
                                Админка
                            </a>
                        @endif
                    @endauth

                    {{-- Поиск --}}
                    <form method="GET" action="{{ url('/abooks') }}" class="relative">
                        <input type="text" name="search" placeholder="Поиск..."
                               value="{{ request('search') }}"
                               class="rounded px-3 py-1 text-black w-48" />
                        <button type="submit" class="absolute right-1 top-1 text-gray-600 hover:text-gray-900" aria-label="Искать">
                            🔍
                        </button>
                    </form>

                    {{-- Селектор жанров --}}
                    <select name="genre" onchange="location = this.value" class="rounded text-black">
                        <option value="{{ url('/abooks') }}">Все жанры</option>
                        @foreach($allGenres ?? [] as $genre)
                            <option value="{{ url('/abooks?genre='.$genre->id) }}"
                                {{ request('genre') == $genre->id ? 'selected' : '' }}>
                                {{ $genre->name }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Селектор авторов --}}
                    <select name="author" onchange="location = this.value" class="rounded text-black">
                        <option value="{{ url('/abooks') }}">Все авторы</option>
                        @foreach($allAuthors ?? [] as $author)
                            <option value="{{ url('/abooks?author='.$author->id) }}"
                                {{ request('author') == $author->id ? 'selected' : '' }}>
                                {{ $author->name }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Селектор исполнителей (читателей) --}}
                    <select name="reader" onchange="location = this.value" class="rounded text-black">
                        <option value="{{ url('/abooks') }}">Все исполнители</option>
                        @foreach($allReaders ?? [] as $reader)
                            <option value="{{ url('/abooks?reader='.$reader->id) }}"
                                {{ request('reader') == $reader->id ? 'selected' : '' }}>
                                {{ $reader->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- ПРАВАЯ ЧАСТЬ: Избранное + Авторизация + переключатель темы --}}
                <div class="flex items-center gap-6 text-sm">
                    {{-- Избранное --}}
                    @auth
                        <a href="{{ route('favorites.index') }}" class="flex items-center gap-1 hover:text-cyan-400">
                            ❤️ Мои избранные
                        </a>
                    @endauth

                    {{-- Авторизация / Гость --}}
                    <div class="relative">
                        @auth
                            <div class="flex items-center gap-2">
                                <span>{{ Auth::user()->name }}</span>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="text-red-400 hover:text-white">Выйти</button>
                                </form>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="hover:text-cyan-400">Войти</a>
                            <a href="{{ route('register') }}" class="hover:text-cyan-400 ml-2">Регистрация</a>
                        @endauth
                    </div>

                    {{-- Переключатель ночной темы --}}
                    <button id="theme-toggle" class="ml-auto px-3 py-1 border rounded text-white hover:bg-gray-700" title="Переключить тему">
                        🌙 / ☀️
                    </button>
                </div>

            </div>
        </header>

        {{-- Заголовок страницы --}}
        @isset($header)
            <header class="bg-white shadow">
                <div class="mx-auto max-w-[1200px] py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        {{-- Контент страницы --}}
        <main class="mx-auto max-w-[1200px] px-4 sm:px-6 lg:px-8">
            @yield('content')
        </main>
    </div>

    <script>
        // Простой переключатель ночной темы
        document.getElementById('theme-toggle')?.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
        });
    </script>
</body>
</html>
