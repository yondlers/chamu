<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicContent extends Model
{
    use HasFactory;

    protected $table = 'topic_contents';

    protected $fillable = [
        'topic_id',
        'subject_id',
        'subheading',
        'content_image',
        'content',
        'sort_order',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }
}
