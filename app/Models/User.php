<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_type_id',
        'school_id',
        'parent_id',
        'curriculum_id',
        'grade_id',
        'country_id',
        'province_id',
        'name',
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'avatar',
        'profile_picture',
        'points',
        'streak',
        'last_login_at',
        'is_super_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'is_super_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'user_id');
    }

    public function siteVisits(): HasMany
    {
        return $this->hasMany(SiteVisit::class, 'user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class, 'user_id');
    }

    public function bursaryApplications(): HasMany
    {
        return $this->hasMany(BursaryApplication::class, 'user_id');
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class, 'user_id');
    }

    public function applicationProfile(): HasOne
    {
        return $this->hasOne(UserApplicationProfile::class, 'user_id');
    }

    public function applicationDocuments(): HasMany
    {
        return $this->hasMany(UserApplicationDocument::class, 'user_id');
    }

    public function tutorApplication(): HasOne
    {
        return $this->hasOne(TutorApplication::class, 'user_id');
    }

    public function tutorBookingsAsLearner(): HasMany
    {
        return $this->hasMany(TutorBooking::class, 'learner_user_id');
    }

    public function tutorReviewsWritten(): HasMany
    {
        return $this->hasMany(TutorReview::class, 'reviewer_user_id');
    }

    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class, 'user_type_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(UserType::class, 'user_roles', 'user_id', 'user_type_id')
            ->withTimestamps();
    }

    public function roleNames(): Collection
    {
        if (! Schema::hasTable('user_roles')) {
            $primary = strtolower((string) ($this->userType?->name ?? ''));

            return collect($primary !== '' ? [$primary] : []);
        }

        $names = $this->relationLoaded('roles')
            ? $this->roles->pluck('name')
            : $this->roles()->pluck('user_types.name');

        $names = $names
            ->map(fn ($name) => strtolower((string) $name))
            ->filter()
            ->unique()
            ->values();

        $primary = strtolower((string) ($this->userType?->name ?? ''));

        if ($primary !== '' && ! $names->contains($primary)) {
            $names->push($primary);
        }

        return $names->values();
    }

    public function hasRole(string $role): bool
    {
        return $this->roleNames()->contains(strtolower(trim($role)));
    }

    public function addRole(string $role): void
    {
        $role = strtolower(trim($role));

        if ($role === '') {
            return;
        }

        $userType = UserType::query()->firstOrCreate(
            ['name' => $role],
            ['description' => ucfirst($role).' account.']
        );

        if (Schema::hasTable('user_roles')) {
            $this->roles()->syncWithoutDetaching([$userType->id]);
            $this->unsetRelation('roles');
        }

        if (blank($this->user_type_id)) {
            $this->forceFill(['user_type_id' => $userType->id])->save();
        }
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function syncRoles(array $roles, ?string $primaryRole = null): void
    {
        $roles = collect($roles)
            ->map(fn ($role) => strtolower(trim((string) $role)))
            ->filter()
            ->unique()
            ->values();

        if ($roles->isEmpty()) {
            return;
        }

        $allowed = ['pupil', 'student', 'tutor', 'teacher', 'parent'];
        $roles = $roles->intersect($allowed)->values();

        if ($roles->isEmpty()) {
            return;
        }

        $types = UserType::query()
            ->whereIn('name', $roles->all())
            ->get(['id', 'name']);

        foreach ($roles as $roleName) {
            if (! $types->contains(fn (UserType $type) => $type->name === $roleName)) {
                $types->push(UserType::query()->firstOrCreate(
                    ['name' => $roleName],
                    ['description' => ucfirst($roleName).' account.']
                ));
            }
        }

        if (Schema::hasTable('user_roles')) {
            $this->roles()->sync($types->pluck('id')->all());
        }

        $primaryRole = strtolower(trim((string) ($primaryRole ?: $roles->first())));
        $primary = $types->firstWhere('name', $primaryRole) ?? $types->first();

        if ($primary) {
            $this->forceFill(['user_type_id' => $primary->id])->save();
        }
    }

    public function isTutor(): bool
    {
        return $this->hasRole('tutor');
    }

    public function isPupil(): bool
    {
        return $this->hasRole('pupil');
    }

    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    public function charadeSessions(): HasMany
    {
        return $this->hasMany(CharadeSession::class, 'user_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function examSessions(): HasMany
    {
        return $this->hasMany(ExamSession::class, 'user_id');
    }

    public function leaderboards(): HasMany
    {
        return $this->hasMany(Leaderboard::class, 'user_id');
    }

    public function questionAttempts(): HasMany
    {
        return $this->hasMany(QuestionAttempt::class, 'user_id');
    }

    public function userNoteDecks(): HasMany
    {
        return $this->hasMany(UserNoteDeck::class, 'user_id');
    }

    public function userSubjectPreferences(): HasMany
    {
        return $this->hasMany(UserSubjectPreference::class, 'user_id');
    }

    public function userSubjectResults(): HasMany
    {
        return $this->hasMany(UserSubjectResult::class, 'user_id');
    }

    public function studentReviews(): HasMany
    {
        return $this->hasMany(UserStudentReview::class, 'user_id');
    }
}
