<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'photo',
        'professor_no',
        'employee_no',
        'is_admin',
        'locked',
        'locked_date',
        'with_hrm_access',
        'with_hrt_access',
        'with_hrp_access',
        'with_cpm_access',
        'has_change_password',
        'is_applicant',
        'access_all_branches',
        'with_expiration',
        'expiration_date',
        'otp_code',
        'with_ld_access',
        'with_mig_access',
        'is_encrypted',
        'is_notify',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'has_change_password' => 'boolean',
    ];
}
