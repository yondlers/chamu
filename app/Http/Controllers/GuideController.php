<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeToChamu;
use App\Models\AuditLog;
use App\Models\Bursary;
use App\Models\BursaryDocumentRequirement;
use App\Models\SiteVisit;
use App\Models\SocialPost;
use App\Models\SocialPostResponse;
use App\Models\User;
use App\Models\UserApplicationDocument;
use App\Models\UserApplicationProfile;
use App\Models\UserSubjectResult;
use App\Support\Social\FacebookGraph;
use App\Support\Social\InstagramGraph;
use App\Support\Social\LinkedInGraph;
use App\Support\Social\SocialImageStorage;
use App\Support\Social\SocialMediaConfig;
use App\Support\Social\ThreadsGraph;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Throwable;

class GuideController extends Controller
{
    public function index()
    {
        $guides = collect(config('chamu_guides.guides', []))
            ->map(fn (array $guide, string $slug) => (object) array_merge(['slug' => $slug], $guide))
            ->values();

        return view('guides.index', ['guides' => $guides]);
    }

    public function show(string $guide)
    {
        $guides = collect(config('chamu_guides.guides', []));
        abort_unless($guides->has($guide), 404);

        $selectedGuide = (object) array_merge(['slug' => $guide], $guides->get($guide));
        $relatedGuides = $guides
            ->except($guide)
            ->take(3)
            ->map(fn (array $relatedGuide, string $slug) => (object) array_merge(['slug' => $slug], $relatedGuide))
            ->values();

        return view('guides.show', [
            'guide' => $selectedGuide,
            'relatedGuides' => $relatedGuides,
        ]);
    }
}
