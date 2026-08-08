<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

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
        $path = parse_url($this->url ?? '', PHP_URL_PATH) ?: '';
        $query = $this->queryParameters();

        if ($this->isCourseBrowseVisit($path)) {
            $filters = $this->filterSummary($query);

            return $filters === []
                ? 'Course browse'
                : 'Course browse · '.implode(' · ', $filters);
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

    private function isCourseBrowseVisit(string $path): bool
    {
        return in_array($this->route_name, ['aps.index', 'course-match.index'], true)
            || in_array($path, ['/aps', '/course-match'], true);
    }

    /**
     * @return array<string, mixed>
     */
    private function queryParameters(): array
    {
        $queryString = parse_url($this->url ?? '', PHP_URL_QUERY) ?: '';
        $query = [];
        parse_str($queryString, $query);

        return $query;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return list<string>
     */
    private function filterSummary(array $query): array
    {
        $parts = [];

        $universityLabels = $this->universityFilterLabels($query);
        if ($universityLabels !== []) {
            $parts[] = count($universityLabels) === 1
                ? 'University: '.$universityLabels[0]
                : 'Universities: '.implode(', ', $universityLabels);
        }

        $facultyId = $this->firstFilledScalar($query['faculty_id'] ?? null);
        if ($facultyId !== null && Schema::hasTable('faculties')) {
            $facultyName = Faculty::query()->where('id', $facultyId)->value('name');
            $parts[] = 'Faculty: '.($facultyName ?: '#'.$facultyId);
        }

        $qualificationTypeId = $this->firstFilledScalar($query['qualification_type_id'] ?? null);
        if ($qualificationTypeId !== null && Schema::hasTable('qualification_types')) {
            $typeName = QualificationType::query()->where('id', $qualificationTypeId)->value('name');
            $parts[] = 'Type: '.($typeName ?: '#'.$qualificationTypeId);
        }

        $termId = $this->firstFilledScalar($query['term_id'] ?? null);
        if ($termId !== null && Schema::hasTable('terms')) {
            $termName = Term::query()->where('id', $termId)->value('name');
            $parts[] = 'Term: '.($termName ?: '#'.$termId);
        }

        $search = $this->firstFilledScalar($query['search'] ?? null);
        if ($search !== null) {
            $parts[] = 'Search: "'.$search.'"';
        }

        return $parts;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return list<string>
     */
    private function universityFilterLabels(array $query): array
    {
        $ids = collect();

        $singleId = $this->firstFilledScalar($query['university_id'] ?? null);
        if ($singleId !== null && is_numeric($singleId)) {
            $ids->push((int) $singleId);
        }

        $universityIds = $query['university_ids'] ?? null;
        if (is_array($universityIds)) {
            foreach ($universityIds as $id) {
                if (is_numeric($id) && (int) $id > 0) {
                    $ids->push((int) $id);
                }
            }
        } elseif ($universityIds !== null && is_numeric($universityIds) && (int) $universityIds > 0) {
            $ids->push((int) $universityIds);
        }

        $ids = $ids->unique()->values();

        if ($ids->isEmpty() || ! Schema::hasTable('universities')) {
            return $ids->map(fn (int $id) => '#'.$id)->all();
        }

        $universities = University::query()
            ->whereIn('id', $ids->all())
            ->get(['id', 'name', 'abbreviation'])
            ->keyBy('id');

        return $ids->map(function (int $id) use ($universities) {
            $university = $universities->get($id);

            if ($university === null) {
                return '#'.$id;
            }

            if (filled($university->abbreviation) && $university->abbreviation !== $university->name) {
                return $university->abbreviation.' ('.$university->name.')';
            }

            return $university->name;
        })->all();
    }

    private function firstFilledScalar(mixed $value): ?string
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $filled = $this->firstFilledScalar($item);
                if ($filled !== null) {
                    return $filled;
                }
            }

            return null;
        }

        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
