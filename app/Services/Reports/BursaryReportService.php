<?php

namespace App\Services\Reports;

use App\Models\Bursary;
use App\Models\User;
use App\Models\UserApplicationDocument;
use App\Models\UserApplicationProfile;
use Illuminate\Support\Collection;

class BursaryReportService
{
    /**
     * @return array{ready: bool, missing: array<int, string>}
     */
    public function readinessFor(User $user): array
    {
        $profile = UserApplicationProfile::query()
            ->where('user_id', $user->id)
            ->first();
        $documents = UserApplicationDocument::query()
            ->where('user_id', $user->id)
            ->get()
            ->filter(fn (UserApplicationDocument $document): bool => $document->existsOnDisk())
            ->groupBy('document_key');

        $academicKeys = UserApplicationDocument::definitions()
            ->where('requirement_group', 'academic_record')
            ->pluck('key');
        $missing = [];

        if ($profile === null) {
            $missing[] = 'Application profile details';
        }

        if ($documents->get('id_document', collect())->isEmpty()) {
            $missing[] = 'ID document';
        }

        if ($documents->get('curriculum_vitae', collect())->isEmpty()) {
            $missing[] = 'CV';
        }

        $hasAcademic = $academicKeys->contains(fn (string $key): bool => $documents->get($key, collect())->isNotEmpty());

        if (! $hasAcademic) {
            $missing[] = 'Academic record or latest marks document';
        }

        return [
            'ready' => $missing === [],
            'missing' => $missing,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function openBursaries(): Collection
    {
        return Bursary::query()
            ->with('company')
            ->where('is_active', true)
            ->where(function ($query) {
                $query
                    ->whereNull('closing_date')
                    ->orWhereDate('closing_date', '>=', now()->toDateString());
            })
            ->orderByRaw('case when closing_date is null then 1 else 0 end')
            ->orderBy('closing_date')
            ->orderBy('title')
            ->get()
            ->map(fn (Bursary $bursary): array => [
                'name' => $bursary->title,
                'company' => $bursary->company?->name ?? 'Bursary provider',
                'field' => $bursary->fields_covered ?: ($bursary->category ?: 'General'),
                'category' => $bursary->category,
                'coverage' => $bursary->coverage_value,
                'closing' => $bursary->closing_date
                    ? $bursary->closing_date->format('d M Y')
                    : ($bursary->closing_date_label ?: 'Not listed'),
                'url' => route('bursaries.show', $bursary),
            ]);
    }
}
