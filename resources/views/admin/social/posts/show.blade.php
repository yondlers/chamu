@extends('layouts.app')

@section('title', ($socialPost->title ?: 'Social Post #'.$socialPost->id).' - Admin - Chamu')

@section('content')
    @php
        $requestPayloadJson = $socialPost->request_payload
            ? json_encode($socialPost->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            : null;
        $responsePayloadJson = $socialPost->response_payload
            ? json_encode($socialPost->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            : null;
    @endphp

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-5 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <a href="{{ route('admin.'.$platform['slug'].'.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#01225E] hover:underline">
                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> {{ $platform['name'] }} posts
                </a>
                <p class="mt-5 text-sm font-bold text-[#01225E]">Stored social post</p>
                <h1 class="mt-1 text-3xl font-bold">{{ $socialPost->title ?: 'Untitled post' }}</h1>
                <p class="mt-2 max-w-3xl text-neutral-500">{{ $platform['name'] }} - {{ $socialPost->statusLabel() }} - {{ $socialPost->user?->name ?? 'Unknown admin' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if (in_array($platform['slug'], ['facebook', 'instagram', 'linkedin', 'threads'], true) && $hasAccessToken && $socialPost->status !== 'published')
                    <form method="POST" action="{{ route('admin.'.$platform['slug'].'.posts.publish', $socialPost) }}">
                        @csrf
                        <button class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2 text-sm font-bold text-white hover:bg-[#001A48]">
                            Publish <i data-lucide="send" style="width:16px;height:16px;"></i>
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.activity-logs.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 text-sm font-bold hover:bg-neutral-50">
                    Activity <i data-lucide="activity" style="width:16px;height:16px;"></i>
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="grid gap-3 md:grid-cols-4">
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Status</p>
                <p class="mt-2 text-lg font-bold">{{ $socialPost->statusLabel() }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $socialPost->updated_at?->format('d M Y H:i') ?? 'N/A' }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Published</p>
                <p class="mt-2 text-lg font-bold">{{ $socialPost->published_at?->format('d M Y H:i') ?? 'Not published' }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $socialPost->external_post_id ?? 'No external ID' }}</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Responses</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($socialPost->responses->count()) }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Saved records</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Endpoint</p>
                <p class="mt-2 break-all text-sm font-bold">{{ $postEndpoint ?? 'Not connected' }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Token hidden from UI</p>
            </div>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-bold">Post content</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="border-b border-neutral-100 pb-3">
                        <dt class="font-semibold text-neutral-500">Message</dt>
                        <dd class="mt-1 whitespace-pre-line font-bold text-neutral-900">{{ $socialPost->message }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-neutral-100 pb-3">
                        <dt class="font-semibold text-neutral-500">Audience</dt>
                        <dd class="text-right font-bold text-neutral-900">{{ $socialPost->audience ?: 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-neutral-100 pb-3">
                        <dt class="font-semibold text-neutral-500">Link</dt>
                        <dd class="max-w-sm truncate text-right font-bold text-neutral-900">{{ $socialPost->link_url ?: 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-neutral-100 pb-3">
                        <dt class="font-semibold text-neutral-500">Media</dt>
                        <dd class="max-w-sm truncate text-right font-bold text-neutral-900">{{ $socialPost->media_url ?: 'N/A' }}</dd>
                    </div>
                    @if ($socialPost->media_url && str($socialPost->media_url)->startsWith(['http://', 'https://']))
                        <div class="border-b border-neutral-100 pb-3">
                            <dt class="font-semibold text-neutral-500">Preview</dt>
                            <dd class="mt-2">
                                <img src="{{ $socialPost->media_url }}" alt="{{ $socialPost->title ?: 'Social post image' }}" class="aspect-square w-full max-w-sm rounded-xl border border-neutral-200 object-cover">
                            </dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-4">
                        <dt class="font-semibold text-neutral-500">Created</dt>
                        <dd class="text-right font-bold text-neutral-900">{{ $socialPost->created_at?->format('d M Y H:i') ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-bold">Request and latest response</h2>
                <div class="mt-4 grid gap-4">
                    <div>
                        <p class="mb-2 text-sm font-bold text-neutral-800">Saved request payload</p>
                        <pre class="max-h-72 overflow-auto rounded-xl bg-neutral-950 p-4 text-xs font-semibold text-white">{{ $requestPayloadJson ?: 'No request payload saved.' }}</pre>
                    </div>
                    <div>
                        <p class="mb-2 text-sm font-bold text-neutral-800">Latest API response</p>
                        <pre class="max-h-72 overflow-auto rounded-xl bg-neutral-950 p-4 text-xs font-semibold text-white">{{ $responsePayloadJson ?: 'No response payload saved yet.' }}</pre>
                    </div>
                </div>
            </div>
        </section>

        @if ($socialPost->error_message)
            <section class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-5">
                <h2 class="text-xl font-bold text-red-800">Last error</h2>
                <p class="mt-2 text-sm font-semibold text-red-700">{{ $socialPost->error_message }}</p>
            </section>
        @endif

        @if ($platform['slug'] === 'facebook')
            <section class="mt-6 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold">Facebook comment</h2>
                        <p class="mt-1 text-sm text-neutral-500">Post a Page comment on the published Facebook post and save the Graph response here.</p>
                    </div>
                    @if (! $socialPost->external_post_id)
                        <span class="inline-flex w-fit rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">Publish first</span>
                    @endif
                </div>

                @if ($socialPost->external_post_id && $hasAccessToken)
                    <form method="POST" action="{{ route('admin.facebook.posts.comments.store', $socialPost) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label for="facebook_comment_message" class="text-sm font-bold text-neutral-800">Comment</label>
                            <textarea id="facebook_comment_message" name="comment_message" rows="4" class="mt-2 w-full resize-y rounded-xl border border-neutral-300 px-4 py-3 text-sm font-semibold outline-none focus:border-[#01225E]" placeholder="Write the Facebook Page comment.">{{ old('comment_message') }}</textarea>
                            @error('comment_message') <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#001A48]">
                            Post comment <i data-lucide="message-circle" style="width:16px;height:16px;"></i>
                        </button>
                    </form>
                @else
                    <p class="rounded-xl border border-neutral-200 bg-neutral-50 p-4 text-sm font-semibold text-neutral-600">
                        {{ $hasAccessToken ? 'Publish this Facebook post before commenting.' : 'Configure the Facebook Page access token before commenting.' }}
                    </p>
                @endif
            </section>
        @endif

        <section class="mt-6 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold">Saved responses</h2>
                <p class="mt-1 text-sm text-neutral-500">Publish responses, API errors, comments, reactions, and future webhook records for this post.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                            <th class="py-3 pr-3">Type</th>
                            <th class="px-3 py-3">External ID</th>
                            <th class="px-3 py-3">Author</th>
                            <th class="px-3 py-3">Body</th>
                            <th class="px-3 py-3">Received</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($socialPost->responses as $response)
                            <tr class="border-b border-neutral-100 align-top">
                                <td class="py-4 pr-3">
                                    <p class="font-bold text-neutral-950">{{ str($response->response_type)->replace('_', ' ')->title() }}</p>
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $response->platform }}</p>
                                </td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">{{ $response->external_response_id ?? 'N/A' }}</td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-700">{{ $response->author_name ?? $response->author_handle ?? 'N/A' }}</td>
                                <td class="px-3 py-4">
                                    <p class="max-w-sm text-sm font-semibold text-neutral-700">{{ $response->body ?: 'No text body' }}</p>
                                    @if ($response->response_payload)
                                        <details class="mt-2">
                                            <summary class="cursor-pointer text-xs font-bold text-[#01225E]">Payload</summary>
                                            <pre class="mt-2 max-h-60 overflow-auto rounded-xl bg-neutral-950 p-3 text-xs font-semibold text-white">{{ json_encode($response->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </details>
                                    @endif
                                </td>
                                <td class="px-3 py-4 text-sm font-semibold text-neutral-600">{{ $response->received_at?->format('d M Y H:i') ?? $response->created_at?->format('d M Y H:i') ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-sm font-semibold text-neutral-500">No responses saved for this post yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
@endsection
