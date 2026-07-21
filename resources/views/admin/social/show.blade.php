@extends('layouts.app')

@section('title', $platform['name'].' - Admin - Chamu')

@section('content')
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-5 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#01225E] hover:underline">
                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Admin dashboard
                </a>
                <p class="mt-5 text-sm font-bold text-[#01225E]">Automated marketing</p>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl text-white" style="background: {{ $platform['accent'] }}">
                        <i data-lucide="{{ $platform['icon'] }}" style="width:24px;height:24px;"></i>
                    </span>
                    <div>
                        <h1 class="text-3xl font-bold">{{ $platform['name'] }}</h1>
                        <p class="mt-1 max-w-3xl text-neutral-500">{{ $platform['surface'] }}</p>
                    </div>
                </div>
            </div>
            <div class="no-scrollbar flex gap-2 overflow-x-auto pb-1">
                @foreach ($socialChannels as $channel)
                    <a
                        href="{{ route('admin.'.$channel['slug'].'.index') }}"
                        @class([
                            'inline-flex shrink-0 items-center gap-2 rounded-xl border px-3 py-2 text-sm font-bold transition',
                            'border-neutral-950 bg-neutral-950 text-white' => $channel['slug'] === $platform['slug'],
                            'border-neutral-200 bg-white text-neutral-900 hover:bg-neutral-50' => $channel['slug'] !== $platform['slug'],
                        ])
                    >
                        <i data-lucide="{{ $channel['icon'] }}" style="width:16px;height:16px;"></i>
                        {{ $channel['name'] }}
                    </a>
                @endforeach
            </div>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
                Please check the post fields and try again.
            </div>
        @endif

        <section class="grid gap-3 md:grid-cols-4">
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Connection</p>
                <p class="mt-2 text-lg font-bold">{{ $hasAccessToken ? 'Access token configured' : $platform['api_state'] }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">
                    {{ $hasAccessToken ? (($graphVersion ?? 'API').' ready for server-side requests') : 'Credentials not stored yet' }}
                </p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Drafts</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($draftCount) }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Chamu admin posts</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Queued</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($queuedCount) }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Ready for publish</p>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-bold uppercase text-neutral-500">Engagement</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($engagementCount) }}</p>
                <p class="mt-1 text-xs font-semibold text-neutral-500">Responses synced</p>
            </div>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
            <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold">Post composer</h2>
                        <p class="mt-1 text-sm text-neutral-500">
                            {{ $hasAccessToken ? 'Create the Chamu-side post payload for the platform API request.' : 'Create the Chamu-side post payload before the API bridge is connected.' }}
                        </p>
                    </div>
                    <span @class([
                        'inline-flex w-fit items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold',
                        'border-emerald-200 bg-emerald-50 text-emerald-700' => $hasAccessToken,
                        'border-amber-200 bg-amber-50 text-amber-700' => ! $hasAccessToken,
                    ])>
                        <i data-lucide="{{ $hasAccessToken ? 'key-round' : 'plug-zap' }}" style="width:14px;height:14px;"></i>
                        {{ $hasAccessToken ? 'Token configured' : 'API pending' }}
                    </span>
                </div>

                <form method="POST" action="{{ route('admin.'.$platform['slug'].'.posts.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="post_title" class="text-sm font-bold text-neutral-800">Campaign title</label>
                            <input id="post_title" name="title" value="{{ old('title') }}" type="text" class="mt-2 w-full rounded-xl border border-neutral-300 px-4 py-2.5 text-sm font-semibold outline-none focus:border-[#01225E]" placeholder="{{ $platform['name'] }} campaign">
                            @error('title') <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="post_audience" class="text-sm font-bold text-neutral-800">Audience</label>
                            <input id="post_audience" name="audience" type="text" class="mt-2 w-full rounded-xl border border-neutral-300 px-4 py-2.5 text-sm font-semibold outline-none focus:border-[#01225E]" value="{{ old('audience', $platform['audience']) }}">
                            @error('audience') <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="post_caption" class="text-sm font-bold text-neutral-800">Caption</label>
                        <textarea id="post_caption" name="message" rows="7" maxlength="{{ $messageMaxLength ?? 5000 }}" class="mt-2 w-full resize-y rounded-xl border border-neutral-300 px-4 py-3 text-sm font-semibold outline-none focus:border-[#01225E]" placeholder="Write the admin post that Chamu will publish to {{ $platform['name'] }}.">{{ old('message') }}</textarea>
                        @if ($platform['slug'] === 'threads')
                            <p class="mt-2 text-xs font-bold text-neutral-500">{{ number_format($messageMaxLength ?? 500) }} characters max on Threads.</p>
                        @endif
                        @error('message') <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label for="post_link" class="text-sm font-bold text-neutral-800">Link</label>
                            <input id="post_link" name="link_url" value="{{ old('link_url') }}" type="url" class="mt-2 w-full rounded-xl border border-neutral-300 px-4 py-2.5 text-sm font-semibold outline-none focus:border-[#01225E]" placeholder="https://">
                            @error('link_url') <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="post_media" class="text-sm font-bold text-neutral-800">{{ in_array($platform['slug'], ['facebook', 'instagram', 'linkedin', 'threads'], true) ? 'Public image URL' : 'Media asset' }}</label>
                            <input id="post_media" name="media_url" value="{{ old('media_url') }}" type="{{ in_array($platform['slug'], ['facebook', 'instagram', 'linkedin', 'threads'], true) ? 'url' : 'text' }}" class="mt-2 w-full rounded-xl border border-neutral-300 px-4 py-2.5 text-sm font-semibold outline-none focus:border-[#01225E]" placeholder="{{ in_array($platform['slug'], ['facebook', 'instagram', 'linkedin', 'threads'], true) ? 'https://' : 'Asset ID or URL' }}">
                            @error('media_url') <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                        </div>
                        @if (in_array($platform['slug'], ['facebook', 'instagram', 'linkedin', 'threads'], true))
                            <div>
                                <label for="post_image_upload" class="text-sm font-bold text-neutral-800">Upload image</label>
                                <input id="post_image_upload" name="image_upload" type="file" accept="image/png,image/jpeg" class="mt-2 w-full rounded-xl border border-neutral-300 px-4 py-2 text-sm font-semibold outline-none file:mr-3 file:rounded-lg file:border-0 file:bg-neutral-100 file:px-3 file:py-1.5 file:text-sm file:font-bold focus:border-[#01225E]">
                                @error('image_upload') <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endif
                        <div>
                            <label for="post_status" class="text-sm font-bold text-neutral-800">Status</label>
                            <select id="post_status" name="status" class="mt-2 w-full rounded-xl border border-neutral-300 px-4 py-2.5 text-sm font-semibold outline-none focus:border-[#01225E]">
                                @foreach (['draft' => 'Draft', 'ready_for_approval' => 'Ready for approval', 'queued' => 'Queued'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'draft') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status') <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button name="intent" value="draft" class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#001A48]">
                            Save draft <i data-lucide="save" style="width:16px;height:16px;"></i>
                        </button>
                        <button name="intent" value="queue" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2.5 text-sm font-bold hover:bg-neutral-50">
                            Queue <i data-lucide="send" style="width:16px;height:16px;"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-bold">Integration readiness</h2>
                <p class="mt-1 text-sm text-neutral-500">{{ $platform['engagement_state'] }}</p>

                @if ($postEndpoint)
                    <div class="mt-5 rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                        <p class="text-xs font-bold uppercase text-neutral-500">Post endpoint</p>
                        <p class="mt-2 break-all text-sm font-bold text-neutral-800">{{ $postEndpoint }}</p>
                    </div>
                @endif

                <div class="mt-5 space-y-3">
                    @foreach ($platform['next_steps'] as $step)
                        <div class="flex gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                            <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-neutral-300 bg-white">
                                <i data-lucide="check" style="width:14px;height:14px;"></i>
                            </span>
                            <p class="text-sm font-semibold text-neutral-700">{{ $step }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 rounded-xl border border-neutral-200 p-4">
                    <p class="text-xs font-bold uppercase text-neutral-500">Publishing flow</p>
                    <div class="mt-4 space-y-3">
                        @foreach (['Draft in Chamu', 'Approve campaign', 'Publish to '.$platform['name'], 'Sync engagement'] as $flowStep)
                            <div class="flex items-center gap-3">
                                <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $platform['accent'] }}"></span>
                                <p class="text-sm font-bold text-neutral-800">{{ $flowStep }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
            <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold">Stored posts</h2>
                        <p class="mt-1 text-sm text-neutral-500">Review saved drafts, queued posts, publish results, and response records.</p>
                    </div>
                    <span class="inline-flex w-fit items-center gap-2 rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-700">
                        <i data-lucide="database" style="width:14px;height:14px;"></i>
                        {{ number_format($posts->total()) }} saved
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[860px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 text-xs uppercase text-neutral-500">
                                <th class="py-3 pr-3">Post</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3">Responses</th>
                                <th class="px-3 py-3">Updated</th>
                                <th class="py-3 pl-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($posts as $post)
                                <tr class="border-b border-neutral-100 align-top">
                                    <td class="py-4 pr-3">
                                        <p class="font-bold text-neutral-950">{{ $post->title ?: 'Untitled post' }}</p>
                                        <p class="mt-1 line-clamp-2 max-w-md text-xs font-semibold text-neutral-500">{{ $post->message }}</p>
                                        @if ($post->external_post_id)
                                            <p class="mt-1 text-xs font-bold text-neutral-500">External ID {{ $post->external_post_id }}</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4">
                                        <span @class([
                                            'inline-flex rounded-full px-3 py-1 text-xs font-bold',
                                            'bg-emerald-50 text-emerald-700' => $post->status === 'published',
                                            'bg-red-50 text-red-700' => $post->status === 'failed',
                                            'bg-amber-50 text-amber-700' => $post->status === 'queued',
                                            'bg-neutral-100 text-neutral-700' => ! in_array($post->status, ['published', 'failed', 'queued'], true),
                                        ])>{{ $post->statusLabel() }}</span>
                                    </td>
                                    <td class="px-3 py-4 text-sm font-semibold text-neutral-700">{{ number_format($post->responses_count) }}</td>
                                    <td class="px-3 py-4 text-sm font-semibold text-neutral-600">{{ $post->updated_at?->format('d M H:i') ?? 'N/A' }}</td>
                                    <td class="py-4 pl-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.'.$platform['slug'].'.posts.show', $post) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 px-3 py-2 text-sm font-bold hover:bg-neutral-50">
                                                Review <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
                                            </a>
                                            @if (in_array($platform['slug'], ['facebook', 'instagram', 'linkedin', 'threads'], true) && $hasAccessToken && $post->status !== 'published')
                                                <form method="POST" action="{{ route('admin.'.$platform['slug'].'.posts.publish', $post) }}">
                                                    @csrf
                                                    <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-3 py-2 text-sm font-bold text-white hover:bg-[#001A48]">
                                                        Publish <i data-lucide="send" style="width:15px;height:15px;"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-sm font-semibold text-neutral-500">No {{ $platform['name'] }} posts stored yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($posts->hasPages())
                    <div class="mt-5">
                        {{ $posts->links() }}
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-xl font-bold">Engagement monitor</h2>
                    <p class="mt-1 text-sm text-neutral-500">{{ $platform['tone'] }}</p>
                </div>
                <div class="grid gap-3 md:grid-cols-3">
                    @foreach (['Comments', 'Messages', 'Reactions'] as $metric)
                        <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                            <p class="text-xs font-bold uppercase text-neutral-500">{{ $metric }}</p>
                            <p class="mt-2 text-2xl font-bold">0</p>
                            <p class="mt-1 text-xs font-semibold text-neutral-500">Not synced</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </main>
@endsection
