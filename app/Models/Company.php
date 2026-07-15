<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'slug',
        'website',
        'logo',
        'description',
        'contact_email',
        'contact_phone',
    ];

    public function bursaries(): HasMany
    {
        return $this->hasMany(Bursary::class, 'company_id');
    }
}
