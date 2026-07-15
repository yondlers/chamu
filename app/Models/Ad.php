<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    use HasFactory;

    protected $table = 'ads';

    protected $fillable = [
        'title',
        'description',
        'image',
        'link',
        'placement',
        'provider',
        'is_active',
        'starts_at',
        'ends_at',
    ];
}
