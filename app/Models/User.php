<?php

namespace App\Models;

use App\Models\User_Info;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'fullname',
        'email',
        'password',
        'company_id',
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
     * The "booted" method of the model.
     *
     * @return void
     */
    // public static function boot()
    // {
    //     parent::boot();

    //     static::created(function ($user) {
    //         // Create a corresponding User_Info record
    //         User_Info::create([
    //             'user_id' => $user->user_id,
    //             'username' => $user->username,
    //             'fullname' => $user->fullname,
    //             'email' => $user->email,
    //             'path' => null,
    //             'dob' => null,
    //             'gender' => null,
    //             'phonenumber' => null,
    //             'house_type' => null,
    //             'house_number' => null,
    //             'street_number' => null,
    //         ]);
    //     });
    // }

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
        static::creating(function ($user) {
            $user->user_id = static::generateUserId();
        });
    }

    /**
     * Generate a unique user ID starting with "0001."
     *
     * @return string
     */
    protected static function generateUserId()
    {
        $lastUser = static::orderByDesc('id')->first();
    if ($lastUser) {
        $lastUserId = (int) ltrim($lastUser->user_id, 'U');
        $nextUserId = 'U' . str_pad($lastUserId + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $nextUserId = 'U001';
    }

        return $nextUserId;
    }

    // public function userInfo()
    // {
    //     return $this->hasOne(User_Info::class, 'user_id');
    // }

    /**
     * Define the relationship between Companies and User models.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    
    /**
     * Get the likes made by the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function likes()
    {
        return $this->hasMany(postlike::class, 'user_id');
    }

    /**
     * Get the comments posted by the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(postcomment::class, 'user_id');
    }

    /**
     * Get the shares made by the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function shares()
    {
        return $this->hasMany(postshare::class, 'user_id');
    }

    public function electricbills()
    {
        return $this->hasMany(electricbills::class, 'user_id');
    }


    public function companies()
    {
        return $this->belongsTo(companies::class, 'company_id', 'company_id');
    }

    public function securitybills()
    {
        return $this->hasMany(securitybills::class, 'user_id');
    }

    public function waterbills()
    {
        return $this->hasMany(waterbills::class, 'user_id');
    }

    public function formgeneral()
    {
        return $this->hasMany(formGeneral::class, 'user_id');
    }

    public function formEnvironment()
    {
        return $this->hasMany(formEnvironment::class, 'user_id');
    }

    public function requestform()
    {
        return $this->hasMany(requestform::class, 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(Companies::class, 'company_id', 'company_id');
    }
    


}
