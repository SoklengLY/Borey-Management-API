<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'company_id', // Add the company_id field to the fillable attributes
    ];

    public function company()
    {
        return $this->belongsTo(companies::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}

