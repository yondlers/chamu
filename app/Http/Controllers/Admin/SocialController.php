<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

class SocialController extends Controller
{
    public function index(string $platform)
    {
        $socialChannel = $this->socialChannel($platform);
        $socialChannels = SocialMediaConfig::adminPlatforms();

        $posts = SocialPost::query()
            ->with('user')
            ->withCount('responses')
            ->where('platform', $socialChannel['slug'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.social.show', [
            'platform' => $socialChannel,
            'socialChannels' => $socialChannels,
            'hasAccessToken' => (bool) $socialChannel['has_access_token'],
            'graphVersion' => match ($socialChannel['slug']) {
                'facebook' => FacebookGraph::graphVersion(),
                'instagram' => InstagramGraph::graphVersion(),
                'linkedin' => LinkedInGraph::restVersion(),
                'threads' => ThreadsGraph::graphVersion(),
                default => null,
            },
            'postEndpoint' => match ($socialChannel['slug']) {
                'facebook' => FacebookGraph::feedEndpoint().' or '.FacebookGraph::photosEndpoint(),
                'instagram' => InstagramGraph::mediaEndpoint().' then '.InstagramGraph::mediaPublishEndpoint(),
                'linkedin' => LinkedInGraph::postsEndpoint().' with optional '.LinkedInGraph::imagesInitializeUploadEndpoint(),
                'threads' => ThreadsGraph::threadsEndpoint().' then '.ThreadsGraph::threadsPublishEndpoint(),
                default => null,
            },
            'draftCount' => SocialPost::where('platform', $socialChannel['slug'])->where('status', 'draft')->count(),
            'queuedCount' => SocialPost::where('platform', $socialChannel['slug'])->where('status', 'queued')->count(),
            'publishedCount' => SocialPost::where('platform', $socialChannel['slug'])->where('status', 'published')->count(),
            'engagementCount' => SocialPostResponse::where('platform', $socialChannel['slug'])->count(),
            'messageMaxLength' => $socialChannel['slug'] === 'threads' ? ThreadsGraph::maxTextLength() : 5000,
            'posts' => $posts,
        ]);
                
    }

    public function store(Request $request, string $platform)
    {
        $socialChannel = $this->socialChannel($platform);

        $messageMaxLength = $socialChannel['slug'] === 'threads' ? ThreadsGraph::maxTextLength() : 5000;
        $validator = Validator::make($request->all(), [
            'title' => ['nullable', 'string', 'max:180'],
            'audience' => ['nullable', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:'.$messageMaxLength],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'media_url' => in_array($socialChannel['slug'], ['instagram', 'linkedin', 'threads'], true)
                ? ['nullable', 'url', 'max:2048']
                : ['nullable', 'string', 'max:2048'],
            'image_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'status' => ['required', Rule::in(['draft', 'ready_for_approval', 'queued'])],
            'intent' => ['nullable', Rule::in(['draft', 'queue'])],
        ]);
        $validator->after(function ($validator) use ($request, $socialChannel) {
            if (
                $socialChannel['slug'] === 'instagram'
                && ! $request->hasFile('image_upload')
                && trim((string) $request->input('media_url')) === ''
            ) {
                $validator->errors()->add('image_upload', 'Upload an image or provide a public image URL for Instagram.');
            }
        });

        $data = $validator->validate();

        $mediaUrl = trim((string) ($data['media_url'] ?? '')) ?: null;

        if ($request->hasFile('image_upload')) {
            $mediaUrl = SocialImageStorage::storePublic($request->file('image_upload'), $socialChannel['slug']);
        }

        $status = ($data['intent'] ?? null) === 'queue' ? 'queued' : $data['status'];
        $facebookHasImage = $socialChannel['slug'] === 'facebook'
            && $mediaUrl !== null
            && str($mediaUrl)->startsWith(['http://', 'https://']);
        $requestPayload = [
            'platform' => $socialChannel['slug'],
            'endpoint' => match ($socialChannel['slug']) {
                'facebook' => $facebookHasImage ? FacebookGraph::photosEndpoint() : FacebookGraph::feedEndpoint(),
                'instagram' => InstagramGraph::mediaEndpoint(),
                'linkedin' => LinkedInGraph::postsEndpoint(),
                'threads' => ThreadsGraph::threadsEndpoint(),
                default => null,
            },
            'publish_endpoint' => match ($socialChannel['slug']) {
                'instagram' => InstagramGraph::mediaPublishEndpoint(),
                'linkedin' => $mediaUrl ? LinkedInGraph::imagesInitializeUploadEndpoint() : null,
                'threads' => ThreadsGraph::threadsPublishEndpoint(),
                default => null,
            },
            'fields' => match ($socialChannel['slug']) {
                'facebook' => $facebookHasImage
                    ? FacebookGraph::safePhotoPayload($data['message'], $mediaUrl)
                    : FacebookGraph::safeFeedPayload($data['message'], ['link' => $data['link_url'] ?? null]),
                'instagram' => InstagramGraph::safeMediaPayload($data['message'], (string) $mediaUrl),
                'linkedin' => LinkedInGraph::safePostPayload($data['message'], LinkedInGraph::authorUrn()) + array_filter([
                    'image_source_url' => $mediaUrl,
                ]),
                'threads' => ThreadsGraph::safeThreadPayload($data['message'], $mediaUrl),
                default => [
                    'message' => $data['message'],
                    'link' => $data['link_url'] ?? null,
                    'media' => $mediaUrl,
                ],
            },
        ];

        $socialPost = new SocialPost;
        $socialPost->fill([
            'user_id' => $request->user()->id,
            'platform' => $socialChannel['slug'],
            'title' => $data['title'] ?? null,
            'message' => $data['message'],
            'audience' => $data['audience'] ?? null,
            'link_url' => $data['link_url'] ?? null,
            'media_url' => $mediaUrl,
            'status' => $status,
            'request_payload' => $requestPayload,
        ]);
        $socialPost->save();

        return redirect()
            ->route('admin.'.$socialChannel['slug'].'.posts.show', $socialPost)
            ->with('status', $socialChannel['name'].' post saved as '.$socialPost->statusLabel().'.');
                
    }

    public function show(SocialPost $socialPost, string $platform)
    {
        $socialChannel = $this->socialChannel($platform);
        $socialChannels = SocialMediaConfig::adminPlatforms();

        abort_unless($socialPost->platform === $socialChannel['slug'], 404);

        $socialPost->load(['user', 'responses' => fn ($query) => $query->latest()]);

        return view('admin.social.posts.show', [
            'platform' => $socialChannel,
            'socialChannels' => $socialChannels,
            'hasAccessToken' => (bool) $socialChannel['has_access_token'],
            'postEndpoint' => match ($socialChannel['slug']) {
                'facebook' => FacebookGraph::feedEndpoint().' or '.FacebookGraph::photosEndpoint(),
                'instagram' => InstagramGraph::mediaEndpoint().' then '.InstagramGraph::mediaPublishEndpoint(),
                'linkedin' => LinkedInGraph::postsEndpoint().' with optional '.LinkedInGraph::imagesInitializeUploadEndpoint(),
                'threads' => ThreadsGraph::threadsEndpoint().' then '.ThreadsGraph::threadsPublishEndpoint(),
                default => null,
            },
            'socialPost' => $socialPost,
        ]);
                
    }

    public function publish(SocialPost $socialPost, string $platform)
    {
        $socialChannel = $this->socialChannel($platform);

        abort_unless($socialPost->platform === $socialChannel['slug'], 404);

        $promotedMediaUrl = SocialImageStorage::promoteStorageUrl($socialPost->media_url, $socialChannel['slug']);

        if ($promotedMediaUrl !== $socialPost->media_url) {
            $socialPost->forceFill(['media_url' => $promotedMediaUrl])->save();
        }

        $facebookHasImage = $socialChannel['slug'] === 'facebook'
            && filled($socialPost->media_url)
            && str($socialPost->media_url)->startsWith(['http://', 'https://']);
        $requestPayload = [
            'platform' => $socialChannel['slug'],
            'endpoint' => match ($socialChannel['slug']) {
                'facebook' => $facebookHasImage ? FacebookGraph::photosEndpoint() : FacebookGraph::feedEndpoint(),
                'instagram' => InstagramGraph::mediaEndpoint(),
                'linkedin' => LinkedInGraph::postsEndpoint(),
                'threads' => ThreadsGraph::threadsEndpoint(),
                default => null,
            },
            'publish_endpoint' => match ($socialChannel['slug']) {
                'instagram' => InstagramGraph::mediaPublishEndpoint(),
                'linkedin' => $socialPost->media_url ? LinkedInGraph::imagesInitializeUploadEndpoint() : null,
                'threads' => ThreadsGraph::threadsPublishEndpoint(),
                default => null,
            },
            'fields' => match ($socialChannel['slug']) {
                'facebook' => $facebookHasImage
                    ? FacebookGraph::safePhotoPayload($socialPost->message, (string) $socialPost->media_url)
                    : FacebookGraph::safeFeedPayload($socialPost->message, ['link' => $socialPost->link_url]),
                'instagram' => InstagramGraph::safeMediaPayload($socialPost->message, (string) $socialPost->media_url),
                'linkedin' => LinkedInGraph::safePostPayload($socialPost->message, LinkedInGraph::authorUrn()) + array_filter([
                    'image_source_url' => $socialPost->media_url,
                ]),
                'threads' => ThreadsGraph::safeThreadPayload($socialPost->message, $socialPost->media_url),
                default => [
                    'message' => $socialPost->message,
                    'link' => $socialPost->link_url,
                    'media' => $socialPost->media_url,
                ],
            },
        ];

        if (! in_array($socialChannel['slug'], ['facebook', 'instagram', 'linkedin', 'threads'], true)) {
            $message = $socialChannel['name'].' publishing is not connected yet.';

            $socialPost->fill([
                'status' => 'failed',
                'request_payload' => $requestPayload,
                'error_message' => $message,
            ]);
            $socialPost->save();

            $responseRecord = new SocialPostResponse;
            $responseRecord->fill([
                'social_post_id' => $socialPost->id,
                'platform' => $socialPost->platform,
                'response_type' => 'publish_blocked',
                'body' => $message,
                'request_payload' => $requestPayload,
                'response_payload' => ['message' => $message],
                'received_at' => now(),
            ]);
            $responseRecord->save();

            return redirect()
                ->route('admin.'.$socialChannel['slug'].'.posts.show', $socialPost)
                ->with('status', $message);
        }

        if ($socialChannel['slug'] === 'instagram') {
            if (trim((string) $socialPost->media_url) === '') {
                $message = 'Instagram publishing needs a public image URL.';

                $socialPost->fill([
                    'status' => 'failed',
                    'request_payload' => $requestPayload,
                    'error_message' => $message,
                ]);
                $socialPost->save();

                $responseRecord = new SocialPostResponse;
                $responseRecord->fill([
                    'social_post_id' => $socialPost->id,
                    'platform' => $socialPost->platform,
                    'response_type' => 'publish_blocked',
                    'body' => $message,
                    'request_payload' => $requestPayload,
                    'response_payload' => ['message' => $message],
                    'received_at' => now(),
                ]);
                $responseRecord->save();

                return redirect()
                    ->route('admin.instagram.posts.show', $socialPost)
                    ->with('status', $message);
            }

            try {
                $containerResponse = InstagramGraph::createMediaContainer($socialPost->message, $socialPost->media_url);
                $containerPayload = $containerResponse->json();
                $containerPayload = is_array($containerPayload) ? $containerPayload : ['body' => $containerResponse->body()];
                $creationId = (string) data_get($containerPayload, 'id', '');
                $requestPayload['publish_fields'] = $creationId !== ''
                    ? InstagramGraph::safePublishPayload($creationId)
                    : [];

                if (! $containerResponse->successful() || $creationId === '') {
                    $errorMessage = data_get($containerPayload, 'error.message') ?: $containerResponse->body();

                    $socialPost->fill([
                        'status' => 'failed',
                        'request_payload' => $requestPayload,
                        'response_payload' => ['media_container' => $containerPayload],
                        'error_message' => $errorMessage,
                    ]);
                    $socialPost->save();

                    $responseRecord = new SocialPostResponse;
                    $responseRecord->fill([
                        'social_post_id' => $socialPost->id,
                        'platform' => $socialPost->platform,
                        'response_type' => 'container_error',
                        'external_response_id' => $creationId !== '' ? $creationId : null,
                        'body' => $errorMessage,
                        'request_payload' => $requestPayload,
                        'response_payload' => $containerPayload,
                        'received_at' => now(),
                    ]);
                    $responseRecord->save();

                    return redirect()
                        ->route('admin.instagram.posts.show', $socialPost)
                        ->with('status', 'Instagram media container failed and response was saved.');
                }

                $publishResponse = InstagramGraph::publishMediaContainer($creationId);
                $publishPayload = $publishResponse->json();
                $publishPayload = is_array($publishPayload) ? $publishPayload : ['body' => $publishResponse->body()];
                $externalPostId = data_get($publishPayload, 'id');
                $errorMessage = $publishResponse->successful()
                    ? null
                    : (data_get($publishPayload, 'error.message') ?: $publishResponse->body());
                $responsePayload = [
                    'media_container' => $containerPayload,
                    'publish' => $publishPayload,
                ];

                $socialPost->fill([
                    'status' => $publishResponse->successful() ? 'published' : 'failed',
                    'external_post_id' => $publishResponse->successful() ? $externalPostId : $socialPost->external_post_id,
                    'request_payload' => $requestPayload,
                    'response_payload' => $responsePayload,
                    'error_message' => $errorMessage,
                    'published_at' => $publishResponse->successful() ? now() : $socialPost->published_at,
                ]);
                $socialPost->save();

                $responseRecord = new SocialPostResponse;
                $responseRecord->fill([
                    'social_post_id' => $socialPost->id,
                    'platform' => $socialPost->platform,
                    'response_type' => $publishResponse->successful() ? 'publish' : 'publish_error',
                    'external_response_id' => $publishResponse->successful() ? $externalPostId : $creationId,
                    'body' => $errorMessage,
                    'request_payload' => $requestPayload,
                    'response_payload' => $responsePayload,
                    'received_at' => now(),
                ]);
                $responseRecord->save();

                return redirect()
                    ->route('admin.instagram.posts.show', $socialPost)
                    ->with('status', $publishResponse->successful() ? 'Instagram post published and response saved.' : 'Instagram publish failed and response was saved.');
            } catch (Throwable $exception) {
                $socialPost->fill([
                    'status' => 'failed',
                    'request_payload' => $requestPayload,
                    'error_message' => $exception->getMessage(),
                ]);
                $socialPost->save();

                $responseRecord = new SocialPostResponse;
                $responseRecord->fill([
                    'social_post_id' => $socialPost->id,
                    'platform' => $socialPost->platform,
                    'response_type' => 'publish_exception',
                    'body' => $exception->getMessage(),
                    'request_payload' => $requestPayload,
                    'response_payload' => ['message' => $exception->getMessage()],
                    'received_at' => now(),
                ]);
                $responseRecord->save();

                return redirect()
                    ->route('admin.instagram.posts.show', $socialPost)
                    ->with('status', 'Instagram publish threw an exception and the response was saved.');
            }
        }

        if ($socialChannel['slug'] === 'linkedin') {
            $missingLinkedInConfig = collect([
                LinkedInGraph::accessToken() === null ? 'OAuth access token' : null,
                LinkedInGraph::authorUrn() === null ? 'author URN' : null,
            ])->filter()->implode(' and ');

            if ($missingLinkedInConfig !== '') {
                $message = 'LinkedIn publishing needs '.$missingLinkedInConfig.'.';

                $socialPost->fill([
                    'status' => 'failed',
                    'request_payload' => $requestPayload,
                    'error_message' => $message,
                ]);
                $socialPost->save();

                $responseRecord = new SocialPostResponse;
                $responseRecord->fill([
                    'social_post_id' => $socialPost->id,
                    'platform' => $socialPost->platform,
                    'response_type' => 'publish_blocked',
                    'body' => $message,
                    'request_payload' => $requestPayload,
                    'response_payload' => ['message' => $message],
                    'received_at' => now(),
                ]);
                $responseRecord->save();

                return redirect()
                    ->route('admin.linkedin.posts.show', $socialPost)
                    ->with('status', $message);
            }

            try {
                $imageUrn = null;
                $imageUploadPayload = null;

                if (trim((string) $socialPost->media_url) !== '') {
                    $imageContents = null;
                    $imageContentType = 'application/octet-stream';
                    $mediaPath = parse_url($socialPost->media_url, PHP_URL_PATH);
                    $storagePath = is_string($mediaPath) && str_starts_with($mediaPath, '/storage/')
                        ? Str::after($mediaPath, '/storage/')
                        : null;

                    if ($storagePath !== null && Storage::disk('public')->exists($storagePath)) {
                        $imageContents = Storage::disk('public')->get($storagePath);
                        $imageContentType = Storage::disk('public')->mimeType($storagePath) ?: $imageContentType;
                    } else {
                        $imageResponse = Http::get($socialPost->media_url);
                        if ($imageResponse->successful()) {
                            $imageContents = $imageResponse->body();
                            $imageContentType = $imageResponse->header('Content-Type') ?: $imageContentType;
                        }
                    }

                    if ($imageContents === null || $imageContents === '') {
                        $message = 'LinkedIn image upload needs a readable image source.';

                        $socialPost->fill([
                            'status' => 'failed',
                            'request_payload' => $requestPayload,
                            'response_payload' => ['image_source_url' => $socialPost->media_url],
                            'error_message' => $message,
                        ]);
                        $socialPost->save();

                        $responseRecord = new SocialPostResponse;
                        $responseRecord->fill([
                            'social_post_id' => $socialPost->id,
                            'platform' => $socialPost->platform,
                            'response_type' => 'image_upload_blocked',
                            'body' => $message,
                            'request_payload' => $requestPayload,
                            'response_payload' => ['image_source_url' => $socialPost->media_url],
                            'received_at' => now(),
                        ]);
                        $responseRecord->save();

                        return redirect()
                            ->route('admin.linkedin.posts.show', $socialPost)
                            ->with('status', $message);
                    }

                    $initializeResponse = LinkedInGraph::initializeImageUpload();
                    $initializePayload = $initializeResponse->json();
                    $initializePayload = is_array($initializePayload) ? $initializePayload : ['body' => $initializeResponse->body()];
                    $imageUrn = data_get($initializePayload, 'value.image');
                    $uploadUrl = data_get($initializePayload, 'value.uploadUrl');

                    if (! $initializeResponse->successful() || ! is_string($imageUrn) || ! is_string($uploadUrl)) {
                        $errorMessage = data_get($initializePayload, 'message') ?: data_get($initializePayload, 'error.message') ?: $initializeResponse->body();

                        $socialPost->fill([
                            'status' => 'failed',
                            'request_payload' => $requestPayload,
                            'response_payload' => ['image_initialize' => $initializePayload],
                            'error_message' => $errorMessage,
                        ]);
                        $socialPost->save();

                        $responseRecord = new SocialPostResponse;
                        $responseRecord->fill([
                            'social_post_id' => $socialPost->id,
                            'platform' => $socialPost->platform,
                            'response_type' => 'image_initialize_error',
                            'external_response_id' => is_string($imageUrn) ? $imageUrn : null,
                            'body' => $errorMessage,
                            'request_payload' => $requestPayload,
                            'response_payload' => $initializePayload,
                            'received_at' => now(),
                        ]);
                        $responseRecord->save();

                        return redirect()
                            ->route('admin.linkedin.posts.show', $socialPost)
                            ->with('status', 'LinkedIn image upload initialization failed and response was saved.');
                    }

                    $imageUploadResponse = LinkedInGraph::uploadImage($uploadUrl, $imageContents, $imageContentType);
                    $imageUploadPayload = [
                        'status' => $imageUploadResponse->status(),
                        'body' => $imageUploadResponse->body(),
                        'image' => $imageUrn,
                    ];

                    if (! $imageUploadResponse->successful()) {
                        $errorMessage = $imageUploadResponse->body();

                        $socialPost->fill([
                            'status' => 'failed',
                            'request_payload' => $requestPayload,
                            'response_payload' => [
                                'image_initialize' => $initializePayload,
                                'image_upload' => $imageUploadPayload,
                            ],
                            'error_message' => $errorMessage,
                        ]);
                        $socialPost->save();

                        $responseRecord = new SocialPostResponse;
                        $responseRecord->fill([
                            'social_post_id' => $socialPost->id,
                            'platform' => $socialPost->platform,
                            'response_type' => 'image_upload_error',
                            'external_response_id' => $imageUrn,
                            'body' => $errorMessage,
                            'request_payload' => $requestPayload,
                            'response_payload' => $imageUploadPayload,
                            'received_at' => now(),
                        ]);
                        $responseRecord->save();

                        return redirect()
                            ->route('admin.linkedin.posts.show', $socialPost)
                            ->with('status', 'LinkedIn image upload failed and response was saved.');
                    }
                }

                $requestPayload['fields'] = LinkedInGraph::safePostPayload($socialPost->message, LinkedInGraph::authorUrn(), $imageUrn, $socialPost->title);
                $response = LinkedInGraph::createPost($socialPost->message, $imageUrn, $socialPost->title);
                $responsePayload = $response->json();
                $responsePayload = is_array($responsePayload) ? $responsePayload : ['body' => $response->body()];
                $externalPostId = $response->header('x-restli-id') ?: data_get($responsePayload, 'id');
                $errorMessage = $response->successful()
                    ? null
                    : (data_get($responsePayload, 'message') ?: data_get($responsePayload, 'error.message') ?: $response->body());

                $savedResponsePayload = [
                    'publish' => $responsePayload,
                ];

                if (isset($initializePayload)) {
                    $savedResponsePayload['image_initialize'] = $initializePayload;
                }

                if ($imageUploadPayload !== null) {
                    $savedResponsePayload['image_upload'] = $imageUploadPayload;
                }

                $socialPost->fill([
                    'status' => $response->successful() ? 'published' : 'failed',
                    'external_post_id' => $response->successful() ? $externalPostId : $socialPost->external_post_id,
                    'request_payload' => $requestPayload,
                    'response_payload' => $savedResponsePayload,
                    'error_message' => $errorMessage,
                    'published_at' => $response->successful() ? now() : $socialPost->published_at,
                ]);
                $socialPost->save();

                $responseRecord = new SocialPostResponse;
                $responseRecord->fill([
                    'social_post_id' => $socialPost->id,
                    'platform' => $socialPost->platform,
                    'response_type' => $response->successful() ? 'publish' : 'publish_error',
                    'external_response_id' => $response->successful() ? $externalPostId : $imageUrn,
                    'body' => $errorMessage,
                    'request_payload' => $requestPayload,
                    'response_payload' => $savedResponsePayload,
                    'received_at' => now(),
                ]);
                $responseRecord->save();

                return redirect()
                    ->route('admin.linkedin.posts.show', $socialPost)
                    ->with('status', $response->successful() ? 'LinkedIn post published and response saved.' : 'LinkedIn publish failed and response was saved.');
            } catch (Throwable $exception) {
                $socialPost->fill([
                    'status' => 'failed',
                    'request_payload' => $requestPayload,
                    'error_message' => $exception->getMessage(),
                ]);
                $socialPost->save();

                $responseRecord = new SocialPostResponse;
                $responseRecord->fill([
                    'social_post_id' => $socialPost->id,
                    'platform' => $socialPost->platform,
                    'response_type' => 'publish_exception',
                    'body' => $exception->getMessage(),
                    'request_payload' => $requestPayload,
                    'response_payload' => ['message' => $exception->getMessage()],
                    'received_at' => now(),
                ]);
                $responseRecord->save();

                return redirect()
                    ->route('admin.linkedin.posts.show', $socialPost)
                    ->with('status', 'LinkedIn publish threw an exception and the response was saved.');
            }
        }

        if ($socialChannel['slug'] === 'threads') {
            try {
                $containerResponse = ThreadsGraph::createThreadContainer($socialPost->message, $socialPost->media_url);
                $containerPayload = ThreadsGraph::responseBodyPayload($containerResponse);
                $creationId = (string) data_get($containerPayload, 'id', '');
                $requestPayload['publish_fields'] = $creationId !== ''
                    ? ThreadsGraph::safePublishPayload($creationId)
                    : [];

                if (! $containerResponse->successful() || $creationId === '') {
                    $errorMessage = ThreadsGraph::errorMessage($containerResponse, 'Threads container');
                    $containerDiagnostics = ThreadsGraph::responseDiagnostics($containerResponse);

                    $socialPost->fill([
                        'status' => 'failed',
                        'request_payload' => $requestPayload,
                        'response_payload' => ['thread_container' => $containerDiagnostics],
                        'error_message' => $errorMessage,
                    ]);
                    $socialPost->save();

                    $responseRecord = new SocialPostResponse;
                    $responseRecord->fill([
                        'social_post_id' => $socialPost->id,
                        'platform' => $socialPost->platform,
                        'response_type' => 'container_error',
                        'external_response_id' => $creationId !== '' ? $creationId : null,
                        'body' => $errorMessage,
                        'request_payload' => $requestPayload,
                        'response_payload' => $containerDiagnostics,
                        'received_at' => now(),
                    ]);
                    $responseRecord->save();

                    return redirect()
                        ->route('admin.threads.posts.show', $socialPost)
                        ->with('status', 'Threads container failed and response was saved.');
                }

                $publishResponse = ThreadsGraph::publishThreadContainer($creationId);
                $publishPayload = ThreadsGraph::responseBodyPayload($publishResponse);
                $externalPostId = data_get($publishPayload, 'id');
                $errorMessage = $publishResponse->successful()
                    ? null
                    : ThreadsGraph::errorMessage($publishResponse, 'Threads publish');
                $responsePayload = [
                    'thread_container' => $containerPayload,
                    'publish' => $publishResponse->successful()
                        ? $publishPayload
                        : ThreadsGraph::responseDiagnostics($publishResponse),
                ];

                $socialPost->fill([
                    'status' => $publishResponse->successful() ? 'published' : 'failed',
                    'external_post_id' => $publishResponse->successful() ? $externalPostId : $socialPost->external_post_id,
                    'request_payload' => $requestPayload,
                    'response_payload' => $responsePayload,
                    'error_message' => $errorMessage,
                    'published_at' => $publishResponse->successful() ? now() : $socialPost->published_at,
                ]);
                $socialPost->save();

                $responseRecord = new SocialPostResponse;
                $responseRecord->fill([
                    'social_post_id' => $socialPost->id,
                    'platform' => $socialPost->platform,
                    'response_type' => $publishResponse->successful() ? 'publish' : 'publish_error',
                    'external_response_id' => $publishResponse->successful() ? $externalPostId : $creationId,
                    'body' => $errorMessage,
                    'request_payload' => $requestPayload,
                    'response_payload' => $responsePayload,
                    'received_at' => now(),
                ]);
                $responseRecord->save();

                return redirect()
                    ->route('admin.threads.posts.show', $socialPost)
                    ->with('status', $publishResponse->successful() ? 'Threads post published and response saved.' : 'Threads publish failed and response was saved.');
            } catch (Throwable $exception) {
                $socialPost->fill([
                    'status' => 'failed',
                    'request_payload' => $requestPayload,
                    'error_message' => $exception->getMessage(),
                ]);
                $socialPost->save();

                $responseRecord = new SocialPostResponse;
                $responseRecord->fill([
                    'social_post_id' => $socialPost->id,
                    'platform' => $socialPost->platform,
                    'response_type' => 'publish_exception',
                    'body' => $exception->getMessage(),
                    'request_payload' => $requestPayload,
                    'response_payload' => ['message' => $exception->getMessage()],
                    'received_at' => now(),
                ]);
                $responseRecord->save();

                return redirect()
                    ->route('admin.threads.posts.show', $socialPost)
                    ->with('status', 'Threads publish threw an exception and the response was saved.');
            }
        }

        try {
            $response = $facebookHasImage
                ? FacebookGraph::postPhoto($socialPost->message, (string) $socialPost->media_url)
                : FacebookGraph::postToFeed($socialPost->message, null, ['link' => $socialPost->link_url]);
            $responsePayload = $response->json();
            $responsePayload = is_array($responsePayload) ? $responsePayload : ['body' => $response->body()];
            $externalPostId = $facebookHasImage
                ? (data_get($responsePayload, 'post_id') ?: data_get($responsePayload, 'id'))
                : data_get($responsePayload, 'id');
            $errorMessage = $response->successful()
                ? null
                : (data_get($responsePayload, 'error.message') ?: $response->body());

            $socialPost->fill([
                'status' => $response->successful() ? 'published' : 'failed',
                'external_post_id' => $response->successful() ? $externalPostId : $socialPost->external_post_id,
                'request_payload' => $requestPayload,
                'response_payload' => $responsePayload,
                'error_message' => $errorMessage,
                'published_at' => $response->successful() ? now() : $socialPost->published_at,
            ]);
            $socialPost->save();

            $responseRecord = new SocialPostResponse;
            $responseRecord->fill([
                'social_post_id' => $socialPost->id,
                'platform' => $socialPost->platform,
                'response_type' => $response->successful() ? 'publish' : 'publish_error',
                'external_response_id' => $externalPostId,
                'body' => $errorMessage,
                'request_payload' => $requestPayload,
                'response_payload' => $responsePayload,
                'received_at' => now(),
            ]);
            $responseRecord->save();

            return redirect()
                ->route('admin.facebook.posts.show', $socialPost)
                ->with('status', $response->successful() ? 'Facebook post published and response saved.' : 'Facebook publish failed and response was saved.');
        } catch (Throwable $exception) {
            $socialPost->fill([
                'status' => 'failed',
                'request_payload' => $requestPayload,
                'error_message' => $exception->getMessage(),
            ]);
            $socialPost->save();

            $responseRecord = new SocialPostResponse;
            $responseRecord->fill([
                'social_post_id' => $socialPost->id,
                'platform' => $socialPost->platform,
                'response_type' => 'publish_exception',
                'body' => $exception->getMessage(),
                'request_payload' => $requestPayload,
                'response_payload' => ['message' => $exception->getMessage()],
                'received_at' => now(),
            ]);
            $responseRecord->save();

            return redirect()
                ->route('admin.facebook.posts.show', $socialPost)
                ->with('status', 'Facebook publish threw an exception and the response was saved.');
        }
                
    }

    public function storeFacebookComment(Request $request, SocialPost $socialPost)
    {
        abort_unless($socialPost->platform === 'facebook', 404);

        $data = $request->validate([
            'comment_message' => ['required', 'string', 'max:8000'],
        ]);

        if (blank($socialPost->external_post_id)) {
            $message = 'Publish this Facebook post before commenting.';

            return redirect()
                ->route('admin.facebook.posts.show', $socialPost)
                ->withErrors(['comment_message' => $message]);
        }

        $requestPayload = [
            'platform' => 'facebook',
            'endpoint' => FacebookGraph::commentsEndpoint($socialPost->external_post_id),
            'fields' => FacebookGraph::safeCommentPayload($data['comment_message']),
        ];

        try {
            $response = FacebookGraph::commentOnPost($socialPost->external_post_id, $data['comment_message']);
            $responsePayload = $response->json();
            $responsePayload = is_array($responsePayload) ? $responsePayload : ['body' => $response->body()];
            $externalCommentId = data_get($responsePayload, 'id');
            $errorMessage = $response->successful()
                ? null
                : (data_get($responsePayload, 'error.message') ?: $response->body());

            $responseRecord = new SocialPostResponse;
            $responseRecord->fill([
                'social_post_id' => $socialPost->id,
                'platform' => $socialPost->platform,
                'response_type' => $response->successful() ? 'comment' : 'comment_error',
                'external_response_id' => $externalCommentId,
                'body' => $response->successful() ? $data['comment_message'] : $errorMessage,
                'request_payload' => $requestPayload,
                'response_payload' => $responsePayload,
                'received_at' => now(),
                'responded_at' => $response->successful() ? now() : null,
            ]);
            $responseRecord->save();

            $socialPost->forceFill([
                'last_synced_at' => now(),
            ])->save();

            return redirect()
                ->route('admin.facebook.posts.show', $socialPost)
                ->with('status', $response->successful() ? 'Facebook comment posted and response saved.' : 'Facebook comment failed and response was saved.');
        } catch (Throwable $exception) {
            $responseRecord = new SocialPostResponse;
            $responseRecord->fill([
                'social_post_id' => $socialPost->id,
                'platform' => $socialPost->platform,
                'response_type' => 'comment_exception',
                'body' => $exception->getMessage(),
                'request_payload' => $requestPayload,
                'response_payload' => ['message' => $exception->getMessage()],
                'received_at' => now(),
            ]);
            $responseRecord->save();

            return redirect()
                ->route('admin.facebook.posts.show', $socialPost)
                ->with('status', 'Facebook comment threw an exception and the response was saved.');
        }
                    
    }

    public function fetchFacebookInsights(SocialPost $socialPost)
    {
        abort_unless($socialPost->platform === 'facebook', 404);

        if (blank($socialPost->external_post_id)) {
            $message = 'Publish this Facebook post before fetching insights.';

            return redirect()
                ->route('admin.facebook.posts.show', $socialPost)
                ->withErrors(['insights' => $message]);
        }

        $requestPayload = [
            'platform' => 'facebook',
            'endpoint' => FacebookGraph::insightsEndpoint($socialPost->external_post_id),
            'fields' => FacebookGraph::safeInsightsPayload(FacebookGraph::postInsightMetrics()),
        ];

        try {
            $response = FacebookGraph::getPostInsights($socialPost->external_post_id, FacebookGraph::postInsightMetrics());
            $responsePayload = $response->json();
            $responsePayload = is_array($responsePayload) ? $responsePayload : ['body' => $response->body()];
            $errorMessage = $response->successful()
                ? null
                : (data_get($responsePayload, 'error.message') ?: $response->body());

            $responseRecord = new SocialPostResponse;
            $responseRecord->fill([
                'social_post_id' => $socialPost->id,
                'platform' => $socialPost->platform,
                'response_type' => $response->successful() ? 'insights' : 'insights_error',
                'external_response_id' => $socialPost->external_post_id,
                'body' => $response->successful() ? 'Facebook insights fetched.' : $errorMessage,
                'request_payload' => $requestPayload,
                'response_payload' => $responsePayload,
                'received_at' => now(),
            ]);
            $responseRecord->save();

            $socialPost->forceFill([
                'last_synced_at' => now(),
            ])->save();

            return redirect()
                ->route('admin.facebook.posts.show', $socialPost)
                ->with('status', $response->successful() ? 'Facebook insights fetched and response saved.' : 'Facebook insights failed and response was saved.');
        } catch (Throwable $exception) {
            $responseRecord = new SocialPostResponse;
            $responseRecord->fill([
                'social_post_id' => $socialPost->id,
                'platform' => $socialPost->platform,
                'response_type' => 'insights_exception',
                'external_response_id' => $socialPost->external_post_id,
                'body' => $exception->getMessage(),
                'request_payload' => $requestPayload,
                'response_payload' => ['message' => $exception->getMessage()],
                'received_at' => now(),
            ]);
            $responseRecord->save();

            return redirect()
                ->route('admin.facebook.posts.show', $socialPost)
                ->with('status', 'Facebook insights threw an exception and the response was saved.');
        }
                    
    }

    public function storeInstagramComment(Request $request, SocialPost $socialPost)
    {
        abort_unless($socialPost->platform === 'instagram', 404);

        $data = $request->validate([
            'comment_message' => ['required', 'string', 'max:'.InstagramGraph::COMMENT_MAX_LENGTH],
        ]);

        if (blank($socialPost->external_post_id)) {
            $message = 'Publish this Instagram post before commenting.';

            return redirect()
                ->route('admin.instagram.posts.show', $socialPost)
                ->withErrors(['comment_message' => $message]);
        }

        $requestPayload = [
            'platform' => 'instagram',
            'endpoint' => InstagramGraph::commentsEndpoint($socialPost->external_post_id),
            'fields' => InstagramGraph::safeCommentPayload($data['comment_message']),
        ];

        try {
            $response = InstagramGraph::commentOnMedia($socialPost->external_post_id, $data['comment_message']);
            $responsePayload = $response->json();
            $responsePayload = is_array($responsePayload) ? $responsePayload : ['body' => $response->body()];
            $externalCommentId = data_get($responsePayload, 'id');
            $errorMessage = $response->successful()
                ? null
                : (data_get($responsePayload, 'error.message') ?: $response->body());

            $responseRecord = new SocialPostResponse;
            $responseRecord->fill([
                'social_post_id' => $socialPost->id,
                'platform' => $socialPost->platform,
                'response_type' => $response->successful() ? 'comment' : 'comment_error',
                'external_response_id' => $externalCommentId,
                'body' => $response->successful() ? $data['comment_message'] : $errorMessage,
                'request_payload' => $requestPayload,
                'response_payload' => $responsePayload,
                'received_at' => now(),
                'responded_at' => $response->successful() ? now() : null,
            ]);
            $responseRecord->save();

            $socialPost->forceFill([
                'last_synced_at' => now(),
            ])->save();

            return redirect()
                ->route('admin.instagram.posts.show', $socialPost)
                ->with('status', $response->successful() ? 'Instagram comment posted and response saved.' : 'Instagram comment failed and response was saved.');
        } catch (Throwable $exception) {
            $responseRecord = new SocialPostResponse;
            $responseRecord->fill([
                'social_post_id' => $socialPost->id,
                'platform' => $socialPost->platform,
                'response_type' => 'comment_exception',
                'body' => $exception->getMessage(),
                'request_payload' => $requestPayload,
                'response_payload' => ['message' => $exception->getMessage()],
                'received_at' => now(),
            ]);
            $responseRecord->save();

            return redirect()
                ->route('admin.instagram.posts.show', $socialPost)
                ->with('status', 'Instagram comment threw an exception and the response was saved.');
        }
                    
    }

    public function fetchInstagramInsights(SocialPost $socialPost)
    {
        abort_unless($socialPost->platform === 'instagram', 404);

        if (blank($socialPost->external_post_id)) {
            $message = 'Publish this Instagram post before fetching insights.';

            return redirect()
                ->route('admin.instagram.posts.show', $socialPost)
                ->withErrors(['insights' => $message]);
        }

        $requestPayload = [
            'platform' => 'instagram',
            'endpoint' => InstagramGraph::insightsEndpoint($socialPost->external_post_id),
            'fields' => InstagramGraph::safeInsightsPayload(InstagramGraph::mediaInsightMetrics()),
        ];

        try {
            $response = InstagramGraph::getMediaInsights($socialPost->external_post_id, InstagramGraph::mediaInsightMetrics());
            $responsePayload = $response->json();
            $responsePayload = is_array($responsePayload) ? $responsePayload : ['body' => $response->body()];
            $errorMessage = $response->successful()
                ? null
                : (data_get($responsePayload, 'error.message') ?: $response->body());

            $responseRecord = new SocialPostResponse;
            $responseRecord->fill([
                'social_post_id' => $socialPost->id,
                'platform' => $socialPost->platform,
                'response_type' => $response->successful() ? 'insights' : 'insights_error',
                'external_response_id' => $socialPost->external_post_id,
                'body' => $response->successful() ? 'Instagram insights fetched.' : $errorMessage,
                'request_payload' => $requestPayload,
                'response_payload' => $responsePayload,
                'received_at' => now(),
            ]);
            $responseRecord->save();

            $socialPost->forceFill([
                'last_synced_at' => now(),
            ])->save();

            return redirect()
                ->route('admin.instagram.posts.show', $socialPost)
                ->with('status', $response->successful() ? 'Instagram insights fetched and response saved.' : 'Instagram insights failed and response was saved.');
        } catch (Throwable $exception) {
            $responseRecord = new SocialPostResponse;
            $responseRecord->fill([
                'social_post_id' => $socialPost->id,
                'platform' => $socialPost->platform,
                'response_type' => 'insights_exception',
                'external_response_id' => $socialPost->external_post_id,
                'body' => $exception->getMessage(),
                'request_payload' => $requestPayload,
                'response_payload' => ['message' => $exception->getMessage()],
                'received_at' => now(),
            ]);
            $responseRecord->save();

            return redirect()
                ->route('admin.instagram.posts.show', $socialPost)
                ->with('status', 'Instagram insights threw an exception and the response was saved.');
        }
                    
    }

    public function storeThreadsComment(Request $request, SocialPost $socialPost)
    {
        abort_unless($socialPost->platform === 'threads', 404);

        $data = $request->validate([
            'comment_message' => ['required', 'string', 'max:'.ThreadsGraph::maxTextLength()],
        ]);

        if (blank($socialPost->external_post_id)) {
            $message = 'Publish this Threads post before commenting.';

            return redirect()
                ->route('admin.threads.posts.show', $socialPost)
                ->withErrors(['comment_message' => $message]);
        }

        $requestPayload = [
            'platform' => 'threads',
            'endpoint' => ThreadsGraph::threadsEndpoint(),
            'publish_endpoint' => ThreadsGraph::threadsPublishEndpoint(),
            'fields' => ThreadsGraph::safeThreadPayload($data['comment_message'], null, $socialPost->external_post_id),
        ];

        try {
            $containerResponse = ThreadsGraph::createThreadContainer($data['comment_message'], null, $socialPost->external_post_id);
            $containerPayload = ThreadsGraph::responseBodyPayload($containerResponse);
            $creationId = (string) data_get($containerPayload, 'id', '');
            $requestPayload['publish_fields'] = $creationId !== ''
                ? ThreadsGraph::safePublishPayload($creationId)
                : [];

            if (! $containerResponse->successful() || $creationId === '') {
                $errorMessage = ThreadsGraph::errorMessage($containerResponse, 'Threads reply container');
                $containerDiagnostics = ThreadsGraph::responseDiagnostics($containerResponse);

                $responseRecord = new SocialPostResponse;
                $responseRecord->fill([
                    'social_post_id' => $socialPost->id,
                    'platform' => $socialPost->platform,
                    'response_type' => 'reply_container_error',
                    'external_response_id' => $creationId !== '' ? $creationId : null,
                    'body' => $errorMessage,
                    'request_payload' => $requestPayload,
                    'response_payload' => ['thread_container' => $containerDiagnostics],
                    'received_at' => now(),
                ]);
                $responseRecord->save();

                return redirect()
                    ->route('admin.threads.posts.show', $socialPost)
                    ->with('status', 'Threads reply container failed and response was saved.');
            }

            $publishResponse = ThreadsGraph::publishThreadContainer($creationId);
            $publishPayload = ThreadsGraph::responseBodyPayload($publishResponse);
            $externalReplyId = data_get($publishPayload, 'id');
            $errorMessage = $publishResponse->successful()
                ? null
                : ThreadsGraph::errorMessage($publishResponse, 'Threads reply publish');
            $responsePayload = [
                'thread_container' => $containerPayload,
                'publish' => $publishResponse->successful()
                    ? $publishPayload
                    : ThreadsGraph::responseDiagnostics($publishResponse),
            ];

            $responseRecord = new SocialPostResponse;
            $responseRecord->fill([
                'social_post_id' => $socialPost->id,
                'platform' => $socialPost->platform,
                'response_type' => $publishResponse->successful() ? 'reply' : 'reply_error',
                'external_response_id' => $publishResponse->successful() ? $externalReplyId : $creationId,
                'body' => $publishResponse->successful() ? $data['comment_message'] : $errorMessage,
                'request_payload' => $requestPayload,
                'response_payload' => $responsePayload,
                'received_at' => now(),
                'responded_at' => $publishResponse->successful() ? now() : null,
            ]);
            $responseRecord->save();

            $socialPost->forceFill([
                'last_synced_at' => now(),
            ])->save();

            return redirect()
                ->route('admin.threads.posts.show', $socialPost)
                ->with('status', $publishResponse->successful() ? 'Threads reply posted and response saved.' : 'Threads reply failed and response was saved.');
        } catch (Throwable $exception) {
            $responseRecord = new SocialPostResponse;
            $responseRecord->fill([
                'social_post_id' => $socialPost->id,
                'platform' => $socialPost->platform,
                'response_type' => 'reply_exception',
                'body' => $exception->getMessage(),
                'request_payload' => $requestPayload,
                'response_payload' => ['message' => $exception->getMessage()],
                'received_at' => now(),
            ]);
            $responseRecord->save();

            return redirect()
                ->route('admin.threads.posts.show', $socialPost)
                ->with('status', 'Threads reply threw an exception and the response was saved.');
        }
                    
    }

    public function fetchThreadsInsights(SocialPost $socialPost)
    {
        abort_unless($socialPost->platform === 'threads', 404);

        if (blank($socialPost->external_post_id)) {
            $message = 'Publish this Threads post before fetching insights.';

            return redirect()
                ->route('admin.threads.posts.show', $socialPost)
                ->withErrors(['insights' => $message]);
        }

        $requestPayload = [
            'platform' => 'threads',
            'endpoint' => ThreadsGraph::insightsEndpoint($socialPost->external_post_id),
            'fields' => ThreadsGraph::safeInsightsPayload(ThreadsGraph::insightMetrics()),
        ];

        try {
            $response = ThreadsGraph::getPostInsights($socialPost->external_post_id, ThreadsGraph::insightMetrics());
            $responsePayload = ThreadsGraph::responseBodyPayload($response);
            $errorMessage = $response->successful()
                ? null
                : ThreadsGraph::errorMessage($response, 'Threads insights');

            $responseRecord = new SocialPostResponse;
            $responseRecord->fill([
                'social_post_id' => $socialPost->id,
                'platform' => $socialPost->platform,
                'response_type' => $response->successful() ? 'insights' : 'insights_error',
                'external_response_id' => $socialPost->external_post_id,
                'body' => $response->successful() ? 'Threads insights fetched.' : $errorMessage,
                'request_payload' => $requestPayload,
                'response_payload' => $response->successful()
                    ? $responsePayload
                    : ThreadsGraph::responseDiagnostics($response),
                'received_at' => now(),
            ]);
            $responseRecord->save();

            $socialPost->forceFill([
                'last_synced_at' => now(),
            ])->save();

            return redirect()
                ->route('admin.threads.posts.show', $socialPost)
                ->with('status', $response->successful() ? 'Threads insights fetched and response saved.' : 'Threads insights failed and response was saved.');
        } catch (Throwable $exception) {
            $responseRecord = new SocialPostResponse;
            $responseRecord->fill([
                'social_post_id' => $socialPost->id,
                'platform' => $socialPost->platform,
                'response_type' => 'insights_exception',
                'external_response_id' => $socialPost->external_post_id,
                'body' => $exception->getMessage(),
                'request_payload' => $requestPayload,
                'response_payload' => ['message' => $exception->getMessage()],
                'received_at' => now(),
            ]);
            $responseRecord->save();

            return redirect()
                ->route('admin.threads.posts.show', $socialPost)
                ->with('status', 'Threads insights threw an exception and the response was saved.');
        }
                    
    }

    public function facebook()
    {
        return $this->index('facebook');
    }

    public function instagram()
    {
        return $this->index('instagram');
    }

    public function threads()
    {
        return $this->index('threads');
    }

    public function linkedin()
    {
        return $this->index('linkedin');
    }

    public function storeFacebook(Request $request)
    {
        return $this->store($request, 'facebook');
    }

    public function storeInstagram(Request $request)
    {
        return $this->store($request, 'instagram');
    }

    public function storeThreads(Request $request)
    {
        return $this->store($request, 'threads');
    }

    public function storeLinkedin(Request $request)
    {
        return $this->store($request, 'linkedin');
    }

    public function showFacebook(SocialPost $socialPost)
    {
        return $this->show($socialPost, 'facebook');
    }

    public function showInstagram(SocialPost $socialPost)
    {
        return $this->show($socialPost, 'instagram');
    }

    public function showThreads(SocialPost $socialPost)
    {
        return $this->show($socialPost, 'threads');
    }

    public function showLinkedin(SocialPost $socialPost)
    {
        return $this->show($socialPost, 'linkedin');
    }

    public function publishFacebook(SocialPost $socialPost)
    {
        return $this->publish($socialPost, 'facebook');
    }

    public function publishInstagram(SocialPost $socialPost)
    {
        return $this->publish($socialPost, 'instagram');
    }

    public function publishThreads(SocialPost $socialPost)
    {
        return $this->publish($socialPost, 'threads');
    }

    public function publishLinkedin(SocialPost $socialPost)
    {
        return $this->publish($socialPost, 'linkedin');
    }

    private function socialChannel(string $platform): array
    {
        $socialChannel = SocialMediaConfig::adminPlatform($platform);
        abort_if($socialChannel === null, 404);

        return $socialChannel;
    }
}
