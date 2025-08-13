<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Listen extends Model
{
    protected $fillable = ['user_id', 'a_book_id', 'a_chapter_id', 'position'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chapter()
    {
        return $this->belongsTo(AChapter::class, 'a_chapter_id');
    }

    // Добавляем связь с книгой
    public function book()
    {
        return $this->belongsTo(ABook::class, 'a_book_id');
    }
}
