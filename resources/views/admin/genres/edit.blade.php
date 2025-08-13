{{-- resources/views/admin/genres/edit.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-6">Редактировать жанр</h1>

    @if ($errors->any())
        <div class="mb-4 text-red-600">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.genres.update', $genre) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block mb-1 font-semibold">Новое название жанра:</label>
            <input type="text" name="name" value="{{ old('name', $genre->name) }}" required class="w-full border p-2 rounded">
        </div>

        <div class="flex gap-4">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                💾 Сохранить изменения
            </button>
            <a href="{{ route('admin.genres.index') }}" class="text-gray-600 hover:underline">Отмена</a>
        </div>
    </form>
</div>
@endsection
