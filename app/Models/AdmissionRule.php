<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdmissionRule extends Model
{
    use HasFactory;

    protected $table = 'admission_rules';

    protected $fillable = [
        'code',
        'name',
        'score_type',
        'calculation_method',
        'score_label',
        'score_suffix',
        'max_score',
        'include_life_orientation',
        'life_orientation_subject_id',
        'subject_count',
        'subject_selection_strategy',
        'minimum_pass_type',
        'points_scale',
        'config',
        'description',
        'is_active',
    ];

    protected $casts = [
        'include_life_orientation' => 'boolean',
        'points_scale' => 'array',
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    public function universityAdmissionRules(): HasMany
    {
        return $this->hasMany(UniversityAdmissionRule::class);
    }
}
