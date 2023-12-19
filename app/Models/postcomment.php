<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class postcomment extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'post_id',
        'content',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function userInfo()
    {
        return $this->belongsTo(User_info::class, 'user_id', 'user_id');
    }

    public function companies()
    {
        return $this->belongsTo(Companies::class, 'company_id', 'company_id');
    }
}
