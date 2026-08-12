<?php

use Illuminate\Support\Arr;

if (! function_exists('adsterra_banner')) {
    /**
     * Render a fixed-size Adsterra static banner by dimension key.
     *
     * Example: {!! adsterra_banner('160x300') !!}
     * Aliases: adsterra_banner_160x300(), etc.
     */
    function adsterra_banner(string $size): string
    {
        $banner = config('adsterra.banners.'.$size);

        if (! is_array($banner) || blank($banner['key'] ?? null)) {
            return '';
        }

        $key = (string) $banner['key'];
        $format = (string) ($banner['format'] ?? 'iframe');
        $height = (int) ($banner['height'] ?? 0);
        $width = (int) ($banner['width'] ?? 0);

        return <<<HTML
<script>
  atOptions = {
    'key' : '{$key}',
    'format' : '{$format}',
    'height' : {$height},
    'width' : {$width},
    'params' : {}
  };
</script>
<script src="https://www.highperformanceformat.com/{$key}/invoke.js"></script>
HTML;
    }
}

if (! function_exists('adsterra_banner_160x300')) {
    function adsterra_banner_160x300(): string
    {
        return adsterra_banner('160x300');
    }
}

if (! function_exists('adsterra_banner_160x600')) {
    function adsterra_banner_160x600(): string
    {
        return adsterra_banner('160x600');
    }
}

if (! function_exists('adsterra_banner_728x90')) {
    function adsterra_banner_728x90(): string
    {
        return adsterra_banner('728x90');
    }
}

if (! function_exists('adsterra_banner_320x50')) {
    function adsterra_banner_320x50(): string
    {
        return adsterra_banner('320x50');
    }
}

if (! function_exists('adsterra_banner_468x60')) {
    function adsterra_banner_468x60(): string
    {
        return adsterra_banner('468x60');
    }
}

if (! function_exists('adsterra_banners')) {
    /**
     * @return array<string, array{key: string, format: string, width: int, height: int}>
     */
    function adsterra_banners(): array
    {
        return Arr::where(config('adsterra.banners', []), fn ($banner): bool => is_array($banner));
    }
}
