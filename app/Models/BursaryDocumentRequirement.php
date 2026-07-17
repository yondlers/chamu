<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    public static function defaultEmailSubmissionRequirements(): Collection
    {
        return collect([
            ['key' => 'covering_letter', 'label' => 'Covering letter', 'description' => 'A short letter introducing you and your motivation for the bursary.', 'is_required' => false, 'accepts_multiple' => false, 'requirement_group' => null, 'sort_order' => 10],
            ['key' => 'id_document', 'label' => 'Certified copy of ID document', 'description' => 'Upload the front and back if they are separate files.', 'is_required' => true, 'accepts_multiple' => true, 'requirement_group' => null, 'sort_order' => 20],
            ['key' => 'curriculum_vitae', 'label' => 'Curriculum Vitae', 'description' => 'Current CV with education, achievements, and activities.', 'is_required' => true, 'accepts_multiple' => false, 'requirement_group' => null, 'sort_order' => 30],
            ['key' => 'matric_certificate', 'label' => 'Certified copy of Matric certificate', 'description' => 'Use this if Matric has been completed. One academic record option must be uploaded.', 'is_required' => false, 'accepts_multiple' => false, 'requirement_group' => 'academic_record', 'sort_order' => 40],
            ['key' => 'academic_transcript', 'label' => 'Full academic record or transcript', 'description' => 'Use tertiary institution letterhead where available. One academic record option must be uploaded.', 'is_required' => false, 'accepts_multiple' => false, 'requirement_group' => 'academic_record', 'sort_order' => 50],
            ['key' => 'grade_12_marks', 'label' => 'Grade 12 marks', 'description' => 'Latest Grade 12 marks are accepted when Matric is not complete.', 'is_required' => false, 'accepts_multiple' => false, 'requirement_group' => 'academic_record', 'sort_order' => 60],
            ['key' => 'grade_11_marks', 'label' => 'Grade 11 marks', 'description' => 'Grade 11 final marks may support current Matric applications.', 'is_required' => false, 'accepts_multiple' => false, 'requirement_group' => 'academic_record', 'sort_order' => 70],
            ['key' => 'family_ids', 'label' => 'Family IDs', 'description' => 'Certified ID copies for parents, legal guardian, or spouse, if financial need applies.', 'is_required' => false, 'accepts_multiple' => true, 'requirement_group' => null, 'sort_order' => 80],
            ['key' => 'proof_of_income', 'label' => 'Proof of income', 'description' => 'Payslips, employment letters, or retrenchment letters. Not needed for SASSA grant recipients.', 'is_required' => false, 'accepts_multiple' => true, 'requirement_group' => null, 'sort_order' => 90],
            ['key' => 'special_case_documents', 'label' => 'Special case documents', 'description' => 'Disability Annexure A or Vulnerable Child Declaration, if applicable.', 'is_required' => false, 'accepts_multiple' => true, 'requirement_group' => null, 'sort_order' => 100],
        ])->map(fn (array $requirement) => (object) array_merge(['id' => null], $requirement));
    }
}
