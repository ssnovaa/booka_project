{{-- resources/views/abooks/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4" x-data="audioPlayer({{ $book->chapters->toJson() }}, {{ $book->id }})">

    {{-- Заголовок книги и кнопка редактирования --}}
    <div class="flex items-center justify-between mb-2">
        <h1 class="text-3xl font-bold">{{ $book->title }}</h1>

        @auth
            @if(auth()->user()?->is_admin)
                <a href="{{ route('admin.abooks.edit', $book->id) }}" 
                   class="bg-yellow-400 text-black px-3 py-1 rounded hover:bg-yellow-500 shadow">
                    ✏️ Редактировать
                </a>
            @endif
        @endauth
    </div>

    <p class="text-sm text-gray-600 mb-1">Автор: {{ $book->author->name ?? 'Автор не указан' }}</p>

    {{-- Серия книги (если есть, КЛИКАБЕЛЬНАЯ) --}}
    @if($book->series)
        <div class="mb-3">
            <span class="text-sm text-gray-500">Серия:</span>
            <a href="{{ route('series.show', $book->series->id) }}"
               class="font-semibold text-blue-700 hover:underline">
                {{ $book->series->title }}
            </a>
        </div>
    @endif

    {{-- Жанры --}}
    @if($book->genres && $book->genres->count())
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach($book->genres as $genre)
                <span class="text-xs bg-gray-200 rounded px-2 py-0.5">{{ $genre->name }}</span>
            @endforeach
        </div>
    @endif

    <p class="mb-6">{{ $book->description }}</p>

    {{-- Обложка --}}
    @if($book->cover_url)
        <img src="{{ asset('storage/' . $book->cover_url) }}" alt="Обложка" class="w-64 mb-6 rounded shadow">
    @endif

    @auth
        <form method="POST" action="{{ route('favorites.toggle', $book->id) }}" class="my-4">
            @csrf
            @if (auth()->user()->favoriteBooks->contains($book->id))
                <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded">Убрать из избранного ❤️</button>
            @else
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Добавить в избранное 🤍</button>
            @endif
        </form>
    @endauth

    {{-- --- Блок управления главами для админа --- --}}
    @auth
        @if(auth()->user()?->is_admin)
            <div class="mb-4 flex flex-wrap items-center gap-4">
                <a href="{{ route('admin.chapters.create', $book->id) }}"
                   class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm shadow">
                    ➕ Добавить главу
                </a>
                {{-- Если нужны массовые действия, можно добавить сюда ещё кнопки --}}
            </div>
        @endif
    @endauth
    {{-- --- /Блок управления главами --- --}}

    {{-- Навигация по главам --}}
    <div class="mb-6">
        <h2 class="text-xl font-semibold mb-2">Главы</h2>
        <ul class="space-y-2">
            @foreach ($book->chapters as $chapter)
                <li class="flex items-center gap-2">
                    <button
                        @click="playChapter({{ $chapter->id }}, '{{ route('audio.stream', $chapter->id) }}')"
                        class="px-4 py-2 rounded w-full text-left"
                        :class="{ 'bg-blue-200 font-semibold': currentChapterId === {{ $chapter->id }} }"
                    >
                        {{ $chapter->title }}
                    </button>
                    
                    @auth
                        @if(auth()->user()?->is_admin)
                            {{-- Ссылки на редактирование и удаление главы --}}
                            <a href="{{ route('admin.chapters.edit', [$book->id, $chapter->id]) }}" 
                               class="text-yellow-600 hover:text-yellow-900 px-2">✏️</a>
                            <form action="{{ route('admin.chapters.destroy', [$book->id, $chapter->id]) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Удалить главу?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 px-2">🗑️</button>
                            </form>
                        @endif
                    @endauth
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Проигрыватель --}}
    <div class="bg-white shadow rounded p-4" x-show="currentChapterId !== null">
        <p class="font-semibold mb-2">Сейчас играет: <span x-text="currentTitle"></span></p>

        <audio x-ref="audio" @ended="onEnded" class="w-full mb-4"></audio>

        <div class="flex items-center space-x-4">
            <button @click="rewind(-10)" class="px-3 py-1 bg-gray-200 rounded">« 10с</button>
            <button @click="togglePlayback" class="px-3 py-1 bg-blue-500 text-white rounded" x-text="isPlaying ? 'Пауза' : 'Играть'"></button>
            <button @click="rewind(10)" class="px-3 py-1 bg-gray-200 rounded">10с »</button>

            <label class="ml-4">
                <span class="text-sm">Скорость:</span>
                <select x-model="playbackRate" @change="changeSpeed" class="ml-1 border rounded px-2 py-1 text-sm">
                    <option value="0.75">0.75x</option>
                    <option value="1">1x</option>
                    <option value="1.25">1.25x</option>
                    <option value="1.5">1.5x</option>
                </select>
            </label>
        </div>
    </div>
</div>

<script>
    function audioPlayer(chapters, bookId) {
        return {
            chapters: chapters,
            bookId: bookId,
            currentChapterId: null,
            currentTitle: '',
            isPlaying: false,
            playbackRate: 1,
            saveInterval: null,

            playChapter(id, url) {
                this.currentChapterId = id;
                this.currentTitle = this.chapters.find(c => c.id === id).title;
                const audio = this.$refs.audio;
                audio.src = url;
                audio.playbackRate = this.playbackRate;

                this.loadProgress(this.bookId, id);
                this.startProgressTracking(this.bookId, id);

                audio.play();
                this.isPlaying = true;
            },

            togglePlayback() {
                const audio = this.$refs.audio;
                if (audio.paused) {
                    audio.play();
                    this.isPlaying = true;
                } else {
                    audio.pause();
                    this.isPlaying = false;
                }
            },

            rewind(seconds) {
                const audio = this.$refs.audio;
                audio.currentTime += seconds;
            },

            changeSpeed() {
                this.$refs.audio.playbackRate = this.playbackRate;
            },

            onEnded() {
                this.isPlaying = false;
            },

            async loadProgress(bookId, chapterId) {
                const response = await fetch(`/listen?a_book_id=${bookId}&a_chapter_id=${chapterId}`);
                const data = await response.json();
                const audio = this.$refs.audio;
                audio.currentTime = data.position || 0;
            },

            startProgressTracking(bookId, chapterId) {
                if (this.saveInterval) clearInterval(this.saveInterval);
                this.saveInterval = setInterval(() => {
                    const audio = this.$refs.audio;
                    fetch('/listen/update', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            a_book_id: bookId,
                            a_chapter_id: chapterId,
                            position: Math.floor(audio.currentTime)
                        })
                    });
                }, 10000);
            }
        };
    }
</script>
@endsection
