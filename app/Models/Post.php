<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'content_type',
        'heading',
        'description',
        'image',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function userInfo()
    {
        return $this->belongsTo(User_info::class, 'user_id', 'user_id');
    }

    public function likes()
    {
        return $this->hasMany(postlike::class);
    }

    public function comments()
    {
        return $this->hasMany(postcomment::class);
    }

    public function shares()
    {
        return $this->hasMany(postshare::class);
    }
}
