<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BursaryApplicationDocument extends Model
{
    use HasFactory;

    protected $table = 'bursary_application_documents';

    protected $fillable = [
        'bursary_application_id',
        'bursary_document_requirement_id',
        'document_key',
        'original_name',
        'storage_disk',
        'path',
        'mime_type',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(BursaryApplication::class, 'bursary_application_id');
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(BursaryDocumentRequirement::class, 'bursary_document_requirement_id');
    }
}
