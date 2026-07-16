<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SeoMeta extends Component
{
    /**
     * @param  array<int|string, mixed>|null  $jsonLd
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $canonical,
        public ?string $robots = null,
        public ?string $image = null,
        public ?array $jsonLd = null,
    ) {
        //
    }

    public function render(): View|Closure|string
    {
        return view('components.seo-meta');
    }
}
