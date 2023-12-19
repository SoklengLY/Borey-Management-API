<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    // Define the role names as constants
    const ADMIN = 'admin';
    const COMPANY = 'company';
    const USER = 'user';

    // Define the relationship between Role and User models
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
