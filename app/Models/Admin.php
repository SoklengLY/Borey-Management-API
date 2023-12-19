<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'date_registered',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($admin) {
            $admin->admin_id = static::generateAdminId();
        });
    }

    /**
     * Generate a unique company ID starting with "0001."
     *
     * @return string
     */
    protected static function generateAdminId()
    {
        $lastAdmin = static::orderByDesc('id')->first();
        if ($lastAdmin) {
            $lastAdminId = (int) ltrim($lastAdmin->admin_id, 'A');
            $nextAdminId =  'A' .str_pad($lastAdminId + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextAdminId = 'A001';
        }

        return $nextAdminId;
    }

    /**
     * Define the relationship between Companies and User models.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
