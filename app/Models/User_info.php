<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_info extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $fillable = ['user_id','image_cid', 'dob', 'gender', 'phonenumber', 'house_type', 'house_number', 'street_number'];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

}
