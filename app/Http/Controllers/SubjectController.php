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

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->grade_id === null) {
            return redirect()
                ->route('profile.edit')
                ->with('status', 'Choose your grade before selecting subjects.');
        }

        $subjects = DB::table('subjects')
            ->select('id', 'name', 'code', 'abbreviation', 'sort_order')
            ->where('curriculum_id', $user->curriculum_id)
            ->when($user->grade_id !== null, fn ($query) => $query->where('grade_id', $user->grade_id))
            ->when(Schema::hasColumn('subjects', 'is_live'), fn ($query) => $query->where('is_live', true))
            ->orderBy('name')
            ->get();

        $selectedSubjectIds = DB::table('user_subject_preferences')
            ->where('user_id', $user->id)
            ->where('grade_id', $user->grade_id)
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view('subjects.index', [
            'user' => $user,
            'subjects' => $subjects,
            'selectedSubjectIds' => $selectedSubjectIds,
        ]);
            
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if ($user->grade_id === null) {
            return redirect()
                ->route('profile.edit')
                ->with('status', 'Choose your grade before selecting subjects.');
        }

        $data = $request->validate([
            'subjects' => ['required', 'array', 'min:7'],
            'subjects.*' => ['integer', 'exists:subjects,id'],
        ], [
            'subjects.required' => 'Select at least 7 subjects.',
            'subjects.min' => 'Select at least 7 subjects.',
        ]);

        $subjectIds = collect($data['subjects'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $allowedSubjectIds = DB::table('subjects')
            ->select('id')
            ->where('curriculum_id', $user->curriculum_id)
            ->when($user->grade_id !== null, fn ($query) => $query->where('grade_id', $user->grade_id))
            ->whereIn('id', $subjectIds)
            ->orderBy('name')
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if ($allowedSubjectIds->count() < 7) {
            return back()
                ->withInput()
                ->withErrors(['subjects' => 'Select at least 7 subjects from your grade and curriculum.']);
        }

        DB::table('user_subject_preferences')
            ->where('user_id', $user->id)
            ->where('grade_id', $user->grade_id)
            ->delete();

        foreach ($allowedSubjectIds as $index => $subjectId) {
            DB::table('user_subject_preferences')->insert([
                'user_id' => $user->id,
                'curriculum_id' => $user->curriculum_id,
                'grade_id' => $user->grade_id,
                'subject_id' => $subjectId,
                'sort_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()
            ->route('subjects.index')
            ->with('status', 'Subjects updated.');
            
    }
}
