<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserType extends Model
{
    use HasFactory;

    protected $table = 'user_types';

    protected $fillable = [
        'name',
        'description',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'user_type_id');
    }

    public function roleUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles', 'user_type_id', 'user_id')
            ->withTimestamps();
    }
}
