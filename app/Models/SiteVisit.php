<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteVisit extends Model
{
    use HasFactory;

    protected $table = 'site_visits';

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'method',
        'url',
        'route_name',
        'referrer',
        'user_agent',
        'device_type',
        'platform',
        'browser',
        'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
        ];
    }

    public function pageLabel(): string
    {
        if ($this->isApsUniversityOnlyVisit()) {
            return 'APS page, university selected, no APS yet';
        }

        return $this->url ?? 'Unknown page';
    }

    public function pageDetail(): ?string
    {
        $label = $this->pageLabel();

        return $this->url !== null && $label !== $this->url
            ? $this->url
            : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    private function isApsUniversityOnlyVisit(): bool
    {
        $path = parse_url($this->url ?? '', PHP_URL_PATH) ?: '';
        $queryString = parse_url($this->url ?? '', PHP_URL_QUERY) ?: '';
        $query = [];
        parse_str($queryString, $query);

        if ($this->route_name !== 'aps.index' && $path !== '/aps') {
            return false;
        }

        return $this->hasUniversityFilter($query) && ! $this->hasFilledApsScore($query);
    }

    /**
     * @param array<string, mixed> $query
     */
    private function hasUniversityFilter(array $query): bool
    {
        if (isset($query['university_id']) && trim((string) $query['university_id']) !== '') {
            return true;
        }

        $universityIds = $query['university_ids'] ?? null;

        if (is_array($universityIds)) {
            return collect($universityIds)
                ->contains(fn ($id) => trim((string) $id) !== '');
        }

        return $universityIds !== null && trim((string) $universityIds) !== '';
    }

    /**
     * @param array<string, mixed> $query
     */
    private function hasFilledApsScore(array $query): bool
    {
        if (! isset($query['aps_score'])) {
            return false;
        }

        return ! is_array($query['aps_score'])
            && trim((string) $query['aps_score']) !== '';
    }
}
