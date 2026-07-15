@extends('layouts.app')

@section('title', 'Take Practice · Chamu')

@section('content')
    @php
        $currentStep = $answeredCount + 1;
        $isLastStep = $currentStep >= $totalSubQuestions;
        $progressWidth = $totalSubQuestions > 0 ? round(($currentStep / $totalSubQuestions) * 100) : 0;
        $imagePath = $subQuestion->image;
        $hasImageFile = $imagePath && file_exists(public_path($imagePath));
    @endphp

    <main class="max-w-3xl mx-auto px-5 lg:px-8 py-8">
        <div class="mb-6">
            <p class="text-sm font-semibold text-[#01225E]">{{ $quiz->title }}</p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-neutral-100">
                <div class="h-full rounded-full bg-[#01225E]" style="width: {{ $progressWidth }}%;"></div>
            </div>
            <p class="mt-2 text-sm font-semibold text-neutral-500">Question {{ $currentStep }} of {{ $totalSubQuestions }}</p>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <article class="rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
            <section class="rounded-2xl bg-neutral-50 p-4">
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-[#01225E]">Main question {{ $subQuestion->question_number }}</span>
                    @if ($subQuestion->answer_type)
                        <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-600">{{ $subQuestion->answer_type === 'json' ? 'Label answer' : ucfirst($subQuestion->answer_type) . ' answer' }}</span>
                    @endif
                </div>
                <p class="mt-3 text-sm text-neutral-700">{{ $subQuestion->instructions }}</p>

                @if ($imagePath)
                    @if ($hasImageFile)
                        <img src="{{ asset($imagePath) }}" alt="Question diagram" class="mt-4 max-h-72 w-full rounded-xl border border-neutral-200 object-contain bg-white">
                    @else
                        <div class="mt-4 rounded-xl border border-neutral-200 bg-white px-4 py-3 text-sm font-semibold text-neutral-500">{{ $imagePath }}</div>
                    @endif
                @endif
            </section>

            <form method="POST" action="{{ route('practice.update', $quiz->id) }}" class="mt-6">
                @csrf
                @method('PUT')
                @if (isset($subQuestion->session_question_id))
                    <input type="hidden" name="session_question_id" value="{{ $subQuestion->session_question_id }}">
                @else
                    <input type="hidden" name="sub_question_id" value="{{ $subQuestion->id }}">
                @endif

                <div class="block">
                    <span class="block text-xl font-bold">{{ $subQuestion->sub_question_number }} · {{ $subQuestion->question }}</span>

                    @if ($subQuestion->answer_type === 'json' && ! empty($answerFields))
                        <div class="mt-4 space-y-3">
                            @foreach ($answerFields as $field)
                                <label class="grid gap-2 sm:grid-cols-[4rem_1fr] sm:items-center">
                                    <span class="text-lg font-bold text-[#01225E]">{{ $field }}</span>
                                    <input
                                        type="text"
                                        name="answer[{{ $field }}]"
                                        required
                                        value="{{ old("answer.{$field}") }}"
                                        class="w-full rounded-2xl border border-neutral-300 px-4 py-4 text-lg outline-none focus:border-[#01225E]"
                                        placeholder="{{ $field }} - Type answer"
                                    >
                                </label>
                            @endforeach
                        </div>
                    @else
                        <textarea
                            name="answer"
                            rows="{{ $subQuestion->question_type === 'paragraph' ? 5 : 3 }}"
                            required
                            class="mt-4 w-full rounded-2xl border border-neutral-300 px-4 py-4 text-lg outline-none focus:border-[#01225E]"
                            placeholder="Type your answer"
                        >{{ old('answer') }}</textarea>
                    @endif
                </div>

                <div class="mt-6 flex justify-end">
                    <button class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 font-semibold text-white">
                        {{ $isLastStep ? 'Finish quiz' : 'Continue' }}
                        <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
                    </button>
                </div>
            </form>
        </article>
    </main>
@endsection

@push('scripts')
    <script>
        if (window.history && window.history.pushState) {
            window.history.pushState(null, '', window.location.href);
            window.addEventListener('popstate', () => {
                window.history.pushState(null, '', window.location.href);
                showToast('You can only continue during a quiz.');
            });
        }
    </script>
@endpush
