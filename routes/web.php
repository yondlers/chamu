<?php

use App\Http\Controllers\Admin\AccountController as AdminAccountController;
use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLogController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EmailLogController as AdminEmailLogController;
use App\Http\Controllers\Admin\LemoAiChatController as AdminLemoAiChatController;
use App\Http\Controllers\Admin\SiteVisitController as AdminSiteVisitController;
use App\Http\Controllers\Admin\SocialController as AdminSocialController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApsCalculatorController;
use App\Http\Controllers\ApsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BursaryApplicationController;
use App\Http\Controllers\BursaryController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseMatchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailOpenController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\LemoAiController;
use App\Http\Controllers\MarkController;
use App\Http\Controllers\PracticeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\Public\QualificationController as PublicQualificationController;
use App\Http\Controllers\Public\UniversityController as PublicUniversityController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\TutorApplicationController;
use App\Http\Controllers\UniversityProgrammeController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/aps')->name('home');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::view('/about', 'pages.about')->name('about');
Route::view('/contact', 'pages.contact')->name('contact');
Route::view('/privacy-policy', 'pages.privacy')->name('privacy');
Route::redirect('/privacy', '/privacy-policy');
Route::view('/terms', 'pages.terms')->name('terms');
Route::get('/emails/open/{trackingId}', EmailOpenController::class)->name('emails.open');

Route::get('/guides', [GuideController::class, 'index'])->name('guides.index');
Route::get('/guides/{guide}', [GuideController::class, 'show'])->name('guides.show');
Route::get('/learn', [LearningController::class, 'index'])->name('learn.index');
Route::get('/content', [ContentController::class, 'index'])->name('content.index');

Route::middleware('auth')->group(function () {
    Route::get('/practice/setup', [PracticeController::class, 'setup'])->name('practice.setup');
    Route::post('/practice', [PracticeController::class, 'store'])->name('practice.store');
    Route::get('/practice/{session}', [PracticeController::class, 'show'])->name('practice.show');
    Route::post('/practice/{session}/begin', [PracticeController::class, 'begin'])->name('practice.begin');
    Route::get('/practice/{session}/take', [PracticeController::class, 'take'])->name('practice.take');
    Route::put('/practice/{session}', [PracticeController::class, 'update'])->name('practice.update');
    Route::get('/practice/{session}/results', [PracticeController::class, 'results'])->name('practice.results');
    Route::get('/progress', [ProgressController::class, 'index'])->name('progress.index');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'super.admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('index');

    Route::prefix('facebook')->name('facebook.')->group(function () {
        Route::get('/', [AdminSocialController::class, 'facebook'])->name('index');
        Route::post('/posts', [AdminSocialController::class, 'storeFacebook'])->name('posts.store');
        Route::get('/posts/{socialPost}', [AdminSocialController::class, 'showFacebook'])->name('posts.show');
        Route::post('/posts/{socialPost}/publish', [AdminSocialController::class, 'publishFacebook'])->name('posts.publish');
        Route::post('/posts/{socialPost}/comments', [AdminSocialController::class, 'storeFacebookComment'])->name('posts.comments.store');
        Route::post('/posts/{socialPost}/insights', [AdminSocialController::class, 'fetchFacebookInsights'])->name('posts.insights.fetch');
    });

    Route::prefix('instagram')->name('instagram.')->group(function () {
        Route::get('/', [AdminSocialController::class, 'instagram'])->name('index');
        Route::post('/posts', [AdminSocialController::class, 'storeInstagram'])->name('posts.store');
        Route::get('/posts/{socialPost}', [AdminSocialController::class, 'showInstagram'])->name('posts.show');
        Route::post('/posts/{socialPost}/publish', [AdminSocialController::class, 'publishInstagram'])->name('posts.publish');
        Route::post('/posts/{socialPost}/comments', [AdminSocialController::class, 'storeInstagramComment'])->name('posts.comments.store');
        Route::post('/posts/{socialPost}/insights', [AdminSocialController::class, 'fetchInstagramInsights'])->name('posts.insights.fetch');
    });

    Route::prefix('threads')->name('threads.')->group(function () {
        Route::get('/', [AdminSocialController::class, 'threads'])->name('index');
        Route::post('/posts', [AdminSocialController::class, 'storeThreads'])->name('posts.store');
        Route::get('/posts/{socialPost}', [AdminSocialController::class, 'showThreads'])->name('posts.show');
        Route::post('/posts/{socialPost}/publish', [AdminSocialController::class, 'publishThreads'])->name('posts.publish');
        Route::post('/posts/{socialPost}/comments', [AdminSocialController::class, 'storeThreadsComment'])->name('posts.comments.store');
        Route::post('/posts/{socialPost}/insights', [AdminSocialController::class, 'fetchThreadsInsights'])->name('posts.insights.fetch');
    });

    Route::prefix('linkedin')->name('linkedin.')->group(function () {
        Route::get('/', [AdminSocialController::class, 'linkedin'])->name('index');
        Route::post('/posts', [AdminSocialController::class, 'storeLinkedin'])->name('posts.store');
        Route::get('/posts/{socialPost}', [AdminSocialController::class, 'showLinkedin'])->name('posts.show');
        Route::post('/posts/{socialPost}/publish', [AdminSocialController::class, 'publishLinkedin'])->name('posts.publish');
    });

    Route::get('/accounts', [AdminAccountController::class, 'index'])->name('accounts.index');
    Route::get('/accounts/{user}', [AdminAccountController::class, 'show'])->name('accounts.show');
    Route::get('/emails', [AdminEmailLogController::class, 'index'])->name('emails.index');
    Route::get('/emails/{emailLog}', [AdminEmailLogController::class, 'show'])->name('emails.show');
    Route::get('/lemo-ai', [AdminLemoAiChatController::class, 'index'])->name('lemo-ai.index');
    Route::get('/lemo-ai/{chat}', [AdminLemoAiChatController::class, 'show'])->name('lemo-ai.show');
    Route::get('/site-visits', [AdminSiteVisitController::class, 'index'])->name('site-visits.index');
    Route::get('/site-visits/{siteVisit}', [AdminSiteVisitController::class, 'show'])->name('site-visits.show');
    Route::get('/activity-logs', [AdminActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/{auditLog}', [AdminAuditLogController::class, 'show'])->name('audit-logs.show');
});

Route::get('/aps-calculator', [ApsCalculatorController::class, 'index'])->name('aps-calculator.index');
Route::redirect('/funding', '/bursaries')->name('funding.index');

Route::scopeBindings()->group(function () {
    Route::get('/universities/{university:slug}', [PublicUniversityController::class, 'show'])
        ->name('public.universities.show');
    Route::get('/universities/{university:slug}/qualifications/{qualification:slug}', [PublicQualificationController::class, 'show'])
        ->name('public.qualifications.show');
});

Route::get('/aps', [ApsController::class, 'index'])->name('aps.index');
Route::get('/bursaries', [BursaryController::class, 'index'])->name('bursaries.index');
Route::get('/bursaries/{bursary}', [BursaryController::class, 'show'])->name('bursaries.show');
Route::post('/bursaries/{bursary}/apply', [BursaryApplicationController::class, 'store'])
    ->middleware('auth')
    ->name('bursaries.apply');

Route::get('/lemo-ai', [LemoAiController::class, 'index'])->name('lemo-ai.index');
Route::get('/lemo-ai/{chat}', [LemoAiController::class, 'show'])->name('lemo-ai.show');
Route::post('/lemo-ai/chats', [LemoAiController::class, 'store'])->name('lemo-ai.chats.store');
Route::post('/lemo-ai/messages', [LemoAiController::class, 'storeMessage'])->name('lemo-ai.messages.store');

Route::middleware('auth')->group(function () {
    Route::get('/tools', [ToolController::class, 'index'])->name('tools.index');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{application}/postal-pack', [ApplicationController::class, 'postalPack'])->name('applications.postal-pack');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/application', [ProfileController::class, 'application'])->name('profile.application');
    Route::put('/profile/application', [ProfileController::class, 'updateApplication'])->name('profile.application.update');
    Route::get('/subjects/welcome', [SubjectController::class, 'welcome'])->name('subjects.welcome');
    Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
    Route::put('/subjects', [SubjectController::class, 'update'])->name('subjects.update');
    Route::get('/tutor/welcome', [TutorApplicationController::class, 'welcome'])->name('tutor.application.welcome');
    Route::get('/tutor/application', [TutorApplicationController::class, 'show'])->name('tutor.application.show');
    Route::put('/tutor/application', [TutorApplicationController::class, 'update'])->name('tutor.application.update');
    Route::get('/tutor/coming-soon', [TutorApplicationController::class, 'comingSoon'])->name('tutor.application.coming-soon');
    Route::get('/course-match', [CourseMatchController::class, 'index'])->name('course-match.index');
    Route::get('/universities/{university}/programmes', [UniversityProgrammeController::class, 'index'])->name('universities.programmes');
    Route::get('/courses/{qualification}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/marks', [MarkController::class, 'index'])->name('marks.index');
    Route::put('/marks', [MarkController::class, 'update'])->name('marks.update');
});
