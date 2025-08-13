<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserApiController extends Controller
{
    // Данные профиля пользователя
    public function profile(Request $request)
    {
        $user = $request->user();

        // Избранные книги с авторами
        $favorites = $user->favoriteBooks()->with('author')->get();

        // Прослушанные книги с авторами
        $listened = $user->listenedBooks()->get();

        // Текущая прослушиваемая запись (последняя listen), с загрузкой книги и главы
        $currentListen = $user->listens()
            ->with(['book.author', 'chapter'])
            ->latest()
            ->first();

        return response()->json([
            'id'           => $user->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'is_paid'      => $user->is_paid,
            'favorites'    => $favorites,
            'listened'     => $listened,
            'current_listen' => $currentListen,
        ]);
    }
}
