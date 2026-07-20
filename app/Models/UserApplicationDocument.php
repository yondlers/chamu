<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class UserApplicationDocument extends Model
{
    use HasFactory;

    protected $table = 'user_application_documents';

    protected $fillable = [
        'user_id',
        'document_key',
        'label',
        'original_name',
        'storage_disk',
        'path',
        'mime_type',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public static function definitions(): Collection
    {
        return collect([
            ['key' => 'id_document', 'label' => 'Certified copy of ID document', 'description' => 'Upload the front and back if they are separate files.', 'is_required' => true, 'accepts_multiple' => true, 'requirement_group' => null, 'sort_order' => 10],
            ['key' => 'curriculum_vitae', 'label' => 'Curriculum Vitae', 'description' => 'Current CV with education, achievements, and activities.', 'is_required' => true, 'accepts_multiple' => false, 'requirement_group' => null, 'sort_order' => 20],
            ['key' => 'matric_certificate', 'label' => 'Certified copy of Matric certificate', 'description' => 'Use this if Matric has been completed. One academic document is required.', 'is_required' => false, 'accepts_multiple' => false, 'requirement_group' => 'academic_record', 'sort_order' => 30],
            ['key' => 'academic_transcript', 'label' => 'Full academic record or transcript', 'description' => 'Use tertiary institution letterhead where available. One academic document is required.', 'is_required' => false, 'accepts_multiple' => false, 'requirement_group' => 'academic_record', 'sort_order' => 40],
            ['key' => 'grade_12_marks', 'label' => 'Grade 12 marks', 'description' => 'Latest Grade 12 marks are accepted when Matric is not complete.', 'is_required' => false, 'accepts_multiple' => false, 'requirement_group' => 'academic_record', 'sort_order' => 50],
            ['key' => 'grade_11_marks', 'label' => 'Grade 11 marks', 'description' => 'Grade 11 final marks may support current Matric applications.', 'is_required' => false, 'accepts_multiple' => false, 'requirement_group' => 'academic_record', 'sort_order' => 60],
            ['key' => 'covering_letter', 'label' => 'Motivational or covering letter', 'description' => 'A short letter introducing you and your motivation for funding.', 'is_required' => false, 'accepts_multiple' => false, 'requirement_group' => null, 'sort_order' => 70],
            ['key' => 'family_ids', 'label' => 'Family IDs', 'description' => 'Certified ID copies for parents, legal guardian, or spouse, if financial need applies.', 'is_required' => false, 'accepts_multiple' => true, 'requirement_group' => null, 'sort_order' => 80],
            ['key' => 'proof_of_income', 'label' => 'Proof of income', 'description' => 'Payslips, employment letters, or retrenchment letters. Not needed for SASSA grant recipients.', 'is_required' => false, 'accepts_multiple' => true, 'requirement_group' => null, 'sort_order' => 90],
            ['key' => 'special_case_documents', 'label' => 'Special case documents', 'description' => 'Disability Annexure A or Vulnerable Child Declaration, if applicable.', 'is_required' => false, 'accepts_multiple' => true, 'requirement_group' => null, 'sort_order' => 100],
            ['key' => 'other_documents', 'label' => 'Other supporting documents', 'description' => 'Any additional document a bursary asks for.', 'is_required' => false, 'accepts_multiple' => true, 'requirement_group' => null, 'sort_order' => 110],
        ])->map(fn (array $definition) => (object) $definition);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function existsOnDisk(): bool
    {
        if (blank($this->storage_disk) || blank($this->path)) {
            return false;
        }

        return Storage::disk($this->storage_disk)->exists($this->path);
    }
}
