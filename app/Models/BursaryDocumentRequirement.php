<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BursaryDocumentRequirement extends Model
{
    use HasFactory;

    protected $table = 'bursary_document_requirements';

    protected $fillable = [
        'bursary_id',
        'key',
        'label',
        'description',
        'is_required',
        'accepts_multiple',
        'requirement_group',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'accepts_multiple' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function bursary(): BelongsTo
    {
        return $this->belongsTo(Bursary::class, 'bursary_id');
    }

    public function applicationDocuments(): HasMany
    {
        return $this->hasMany(BursaryApplicationDocument::class, 'bursary_document_requirement_id');
    }
}
