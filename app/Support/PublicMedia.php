<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicMedia
{
    public static function normalizePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $path = str_replace('\\', '/', rawurldecode(trim($path)));
        $urlPath = parse_url($path, PHP_URL_PATH);

        if (is_string($urlPath) && $urlPath !== '') {
            $path = $urlPath;
        }

        foreach (self::basePaths() as $basePath) {
            if (Str::startsWith($path, $basePath . '/')) {
                $path = ltrim(substr($path, strlen($basePath)), '/');
                break;
            }
        }

        $path = ltrim($path, '/');

        foreach ([
            'public/storage/',
            'storage/app/public/',
            'app/public/',
            'public_html/storage/',
            'storage/',
        ] as $prefix) {
            $path = Str::replaceStart($prefix, '', $path);
        }

        if ($path === '' || Str::contains($path, ['..', '\\'])) {
            return null;
        }

        return $path;
    }

    public static function findFile(?string $path): ?string
    {
        $path = self::normalizePath($path);

        if (! $path) {
            return null;
        }

        foreach (self::candidateFiles($path) as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public static function exists(?string $path): bool
    {
        return self::findFile($path) !== null;
    }

    /**
     * Shared-hosting deployments sometimes expose storage from public_html
     * instead of Laravel's public directory. Keep lookup strict to public media.
     */
    protected static function candidateFiles(string $path): array
    {
        return array_values(array_unique(array_filter([
            self::publicDiskPath($path),
            storage_path('app/public/' . $path),
            public_path('storage/' . $path),
            base_path('storage/app/public/' . $path),
            base_path('public/storage/' . $path),
            dirname(base_path()) . '/public_html/storage/' . $path,
            dirname(public_path()) . '/storage/' . $path,
        ])));
    }

    protected static function basePaths(): array
    {
        return array_values(array_unique(array_map(
            fn (string $path) => rtrim(str_replace('\\', '/', $path), '/'),
            array_filter([
                storage_path('app/public'),
                public_path('storage'),
                self::publicDiskRoot(),
                base_path('storage/app/public'),
                base_path('public/storage'),
                dirname(base_path()) . '/public_html/storage',
                dirname(public_path()) . '/storage',
            ])
        )));
    }

    protected static function publicDiskPath(string $path): ?string
    {
        try {
            return Storage::disk('public')->path($path);
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function publicDiskRoot(): ?string
    {
        try {
            return rtrim(Storage::disk('public')->path(''), '/\\');
        } catch (\Throwable) {
            return null;
        }
    }
}
