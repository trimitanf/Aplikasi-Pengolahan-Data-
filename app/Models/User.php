<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEvaluator(): bool
    {
        return $this->role === 'evaluator';
    }

    public function isEvaluatorPenandaan(): bool
    {
        return $this->role === 'evaluator_penandaan';
    }

    public function canAccessIklan(): bool
    {
        return $this->isAdmin() || $this->isEvaluator();
    }

    public function canAccessPenandaan(): bool
    {
        return $this->isAdmin() || $this->isEvaluatorPenandaan();
    }

    public function dataMentah(): HasMany
    {
        return $this->hasMany(DataMentah::class, 'evaluator_id');
    }
}
