<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Модель користувача.
 *
 * Поля:
 * - id: int
 * - name: string
 * - email: string
 * - password: string
 * - is_paid: bool
 * - is_admin: bool   ← прапорець адміністратора
 * - google_id: string|null
 * - avatar_url: string|null
 * - email_verified_at: \Illuminate\Support\Carbon|null
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Поля, дозволені для масового присвоєння.
     * З міркувань безпеки сюди НЕ додаємо is_admin.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_paid',
        'google_id',
        'avatar_url',
    ];

    /**
     * Поля, які мають бути приховані під час серіалізації.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Приведення типів атрибутів моделі.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_paid'           => 'boolean',
            'is_admin'          => 'boolean', // ← додано
        ];
    }

    /**
     * Зв’язок «обрані книги».
     */
    public function favoriteBooks()
    {
        return $this->belongsToMany(\App\Models\ABook::class, 'a_book_user')->withTimestamps();
    }

    /**
     * Зв’язок «прослуховування».
     */
    public function listens()
    {
        return $this->hasMany(\App\Models\Listen::class);
    }

    /**
     * Отримати унікальні книги, які користувач слухав,
     * із завантаженим автором книги.
     * Повертає Builder для подальшої кастомізації запиту.
     */
    public function listenedBooks()
    {
        return \App\Models\ABook::whereIn('id', function ($query) {
            $query->select('a_book_id')
                  ->from('listens')
                  ->where('user_id', $this->id);
        })->with('author');
    }
}
