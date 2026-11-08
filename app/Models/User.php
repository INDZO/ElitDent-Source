<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'KORISNIK';
    protected $primaryKey = 'Email';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'Email',
        'Lozinka',
        'Ime',
        'Prezime',
        'Role'
    ];

    protected $hidden = [
        'Lozinka',
    ];

    public function getAuthPassword(): string
    {
        return $this->Lozinka;
    }
}
