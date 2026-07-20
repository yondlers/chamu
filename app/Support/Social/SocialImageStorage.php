<?php

namespace App\Support\Social;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SocialImageStorage
{
    public static function storePublic(UploadedFile $image, string $platform): string
    {
        $folder = self::publicFolder($platform);
        $filename = Str::uuid()->toString().'.'.$image->extension();

        $image->move(self::ensurePublicDirectory($folder), $filename);

        return url($folder.'/'.$filename);
    }

    public static function promoteStorageUrl(?string $url, string $platform): ?string
    {
        if (blank($url)) {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $legacyPrefix = '/storage/social-posts/'.$platform.'/';

        if (! is_string($path) || ! str_starts_with($path, $legacyPrefix)) {
            return $url;
        }

        $storagePath = Str::after($path, '/storage/');

        if (! Storage::disk('public')->exists($storagePath)) {
            return $url;
        }

        $folder = self::publicFolder($platform);
        $filename = basename($storagePath);
        $publicPath = self::ensurePublicDirectory($folder).DIRECTORY_SEPARATOR.$filename;

        if (! file_exists($publicPath)) {
            file_put_contents($publicPath, Storage::disk('public')->get($storagePath));
        }

        return url($folder.'/'.$filename);
    }

    private static function publicFolder(string $platform): string
    {
        return 'images/social-posts/'.$platform;
    }

    private static function ensurePublicDirectory(string $folder): string
    {
        $directory = public_path($folder);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory;
    }
}
