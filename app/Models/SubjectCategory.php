<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubjectCategory extends Model
{
    use HasFactory;

    protected $table = 'subject_categories';

    protected $fillable = [
        'name',
        'sort_order',
    ];

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'subject_category_id');
    }
}
