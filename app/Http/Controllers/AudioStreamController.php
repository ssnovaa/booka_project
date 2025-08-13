<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AChapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class AudioStreamController extends Controller
{
    /**
     * Потоковая отдача mp3 для одной главы (с защитой, но первая глава — демо).
     * Первая глава книги по порядку доступна всем.
     * Остальные главы — только для авторизованных пользователей
     * или приложению с секретным ключом.
     */
    public function stream($id)
    {
        $chapter = AChapter::findOrFail($id);

        // ЛОГИ для отладки (можно убрать после тестов)
        \Log::info('AUDIO_REQUEST', [
            'chapter_id' => $id,
            'appkey' => request('appkey'),
            'query' => request()->query(),
            'full_url' => request()->fullUrl(),
        ]);

        // Находим первую главу книги (по порядку)
        $firstChapter = AChapter::where('a_book_id', $chapter->a_book_id)
            ->orderBy('order')
            ->first();

        // Твой секретный ключ
        $allowAppKey = ',f,rf vfjrv gjkbdfkf ujcnz enhtyytq hjcs 500hfp';

        // Если НЕ первая глава, пользователь не авторизован и нет appkey — отказ
        if (
            $chapter->id != $firstChapter->id
            && !Auth::check()
            && request('appkey') !== $allowAppKey
        ) {
            abort(403, 'Доступ только для зарегистрированных пользователей');
        }

        // Абсолютный путь к файлу в private storage
        $path = storage_path('app/private/' . $chapter->audio_path);

        if (!file_exists($path)) {
            abort(404, 'Файл не найден');
        }

        // Отдаём mp3 с корректными заголовками для аудиоплеера
        return response()->file($path, [
            'Content-Type' => 'audio/mpeg',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }
}
