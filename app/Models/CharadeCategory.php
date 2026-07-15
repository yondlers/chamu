<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharadeCategory extends Model
{
    use HasFactory;

    protected $table = 'charade_categories';

    protected $fillable = [
        'curriculum_id',
        'subject_id',
        'topic_id',
        'name',
        'description',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id');
    }

    public function charadeCards(): HasMany
    {
        return $this->hasMany(CharadeCard::class, 'charade_category_id');
    }
}
