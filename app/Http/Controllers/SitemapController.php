<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use XMLWriter;

class SitemapController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        return response()->stream(function (): void {
            URL::forceRootUrl($this->configuredHttpsRoot());
            URL::forceScheme('https');

            try {
                $writer = new XMLWriter;
                $writer->openMemory();
                $writer->startDocument('1.0', 'UTF-8');
                $writer->startElement('urlset');
                $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

                $this->writeUrl($writer, route('home'));
                $this->writeStaticPages($writer);
                echo $writer->flush();

                $this->universityRows()->each(function (object $university) use ($writer): void {
                    $this->writeUrl(
                        $writer,
                        route('public.universities.show', ['university' => $university->slug]),
                        $this->lastmod($university->updated_at),
                    );
                    echo $writer->flush();
                });

                $this->qualificationRows()->each(function (object $qualification) use ($writer): void {
                    $this->writeUrl(
                        $writer,
                        route('public.qualifications.show', [
                            'university' => $qualification->university_slug,
                            'qualification' => $qualification->slug,
                        ]),
                        $this->lastmod($qualification->updated_at),
                    );
                    echo $writer->flush();
                });

                $writer->endElement();
                $writer->endDocument();
                echo $writer->flush();
            } finally {
                URL::forceRootUrl(null);
                URL::forceScheme(null);
            }
        }, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function writeStaticPages(XMLWriter $writer): void
    {
        $routeNames = [
            'aps.index',
            'learn.index',
            'guides.index',
            'bursaries.index',
            'about',
            'contact',
            'privacy',
            'terms',
        ];

        foreach ($routeNames as $routeName) {
            $this->writeUrl($writer, route($routeName));
        }

        foreach (array_keys(config('chamu_guides.guides', [])) as $slug) {
            $this->writeUrl($writer, route('guides.show', ['guide' => $slug]));
        }
    }

    private function configuredHttpsRoot(): string
    {
        $root = rtrim((string) config('app.url'), '/');

        if ($root === '') {
            $root = 'https://localhost';
        }

        if (! preg_match('#^https?://#i', $root)) {
            $root = 'https://'.ltrim($root, '/');
        }

        return preg_replace('#^http://#i', 'https://', $root);
    }

    private function universityRows()
    {
        if (! Schema::hasColumn('universities', 'slug')) {
            return collect();
        }

        $query = DB::table('universities')
            ->select('slug', 'updated_at')
            ->whereNotNull('slug')
            ->where('slug', '<>', '')
            ->orderBy('id');

        $this->applyPublicRecordFilters($query, 'universities');

        return $query->cursor();
    }

    private function qualificationRows()
    {
        if (! Schema::hasColumn('universities', 'slug') || ! Schema::hasColumn('qualifications', 'slug')) {
            return collect();
        }

        $query = DB::table('qualifications')
            ->join('universities', 'universities.id', '=', 'qualifications.university_id')
            ->select(
                'qualifications.slug',
                'qualifications.updated_at',
                'universities.slug as university_slug',
            )
            ->whereNotNull('qualifications.slug')
            ->where('qualifications.slug', '<>', '')
            ->whereNotNull('universities.slug')
            ->where('universities.slug', '<>', '')
            ->orderBy('qualifications.id');

        $this->applyPublicRecordFilters($query, 'qualifications');
        $this->applyPublicRecordFilters($query, 'universities');

        return $query->cursor();
    }

    private function applyPublicRecordFilters($query, string $table): void
    {
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull($table.'.deleted_at');
        }

        if (Schema::hasColumn($table, 'is_active')) {
            $query->where($table.'.is_active', true);
        }

        if (Schema::hasColumn($table, 'is_published')) {
            $query->where($table.'.is_published', true);
        }

        if (Schema::hasColumn($table, 'published_at')) {
            $query
                ->whereNotNull($table.'.published_at')
                ->where($table.'.published_at', '<=', now());
        }
    }

    private function writeUrl(XMLWriter $writer, string $loc, ?string $lastmod = null): void
    {
        $writer->startElement('url');
        $writer->writeElement('loc', $loc);

        if ($lastmod !== null) {
            $writer->writeElement('lastmod', $lastmod);
        }

        $writer->endElement();
    }

    private function lastmod(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toAtomString();
        } catch (Throwable) {
            return null;
        }
    }
}
