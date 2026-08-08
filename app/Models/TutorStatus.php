<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutorStatus extends Model
{
    use HasFactory;

    public const PENDING = 'pending';

    public const BLOCKED = 'blocked';

    public const FLAGGED = 'flagged';

    protected $table = 'tutor_statuses';

    protected $fillable = [
        'name',
        'description',
    ];

    public function tutorApplications(): HasMany
    {
        return $this->hasMany(TutorApplication::class, 'tutor_status_id');
    }
}
