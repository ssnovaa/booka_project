<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Проверяем, что пользователь авторизован и является админом
        if (!auth()->check() || !auth()->user()->is_admin) {
            abort(403, 'Доступ запрещён');
        }

        return $next($request);
    }
}
