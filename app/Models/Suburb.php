<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Suburb extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'active',
        'name',
        'city_id',
    ];

    public function getLocationAttribute()
    {
        return $this->name . ', ' . $this->city->name . ', ' . $this->city->province->name;
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
