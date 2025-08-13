<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_paid',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_paid' => 'boolean',
        ];
    }

    public function favoriteBooks()
    {
        return $this->belongsToMany(\App\Models\ABook::class, 'a_book_user')->withTimestamps();
    }

    public function listens()
    {
        return $this->hasMany(\App\Models\Listen::class);
    }

    /**
     * Получить уникальные книги, которые слушал пользователь,
     * с загруженным автором книги.
     * Возвращает Builder для возможности дальнейшей кастомизации запроса.
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
