<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    // Разрешаем массовое заполнение поля name
    protected $fillable = ['name'];

    /**
     * Связь: жанр принадлежит многим книгам (многие ко многим)
     */
    public function books()
    {
        // Название pivot-таблицы 'a_book_genre', связи с моделью ABook
        return $this->belongsToMany(\App\Models\ABook::class, 'a_book_genre')->withTimestamps();
    }
}
