<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class City extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'active',
        'name',
        'province_id',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function suburbs()
    {
        return $this->hasMany(Suburb::class, 'city_id');
    }
}
