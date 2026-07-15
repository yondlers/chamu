<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitType extends Model
{
    use HasFactory;

    protected $fillable = [
        'active',
        'name',
        'description',
    ];

    // Relationships
    public function units()
    {
        return $this->hasMany(Unit::class);
    }
}
