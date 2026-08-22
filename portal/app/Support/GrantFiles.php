<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Single point of truth for stored grant files (audit finding C6).
 *
 * Uploads used to land on the `public` disk, which Apache serves directly:
 * signed contracts, financial reports and ethics certificates were readable by
 * anyone holding or guessing a URL, and the `is_public` flag on a document had
 * no bearing on the file itself. Everything now lives on the private disk and
 * is delivered by a controller that has already run the policy check.
 */
final class GrantFiles
{
    /** The private disk — storage/app/private, outside the document root. */
    public const DISK = 'local';

    /**
     * Store an upload in the given folder and return its relative path.
     */
    public static function store(UploadedFile $file, string $folder): string
    {
        return $file->store($folder, self::DISK);
    }

    /**
     * Replace a stored file, removing the previous one (finding M11 — replaced
     * files used to be orphaned on disk indefinitely).
     */
    public static function replace(UploadedFile $file, string $folder, ?string $previousPath): string
    {
        $path = self::store($file, $folder);

        if ($previousPath) {
            self::delete($previousPath);
        }

        return $path;
    }

    public static function delete(?string $path): void
    {
        if ($path && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public static function exists(?string $path): bool
    {
        return (bool) $path && Storage::disk(self::DISK)->exists($path);
    }

    /**
     * Stream a stored file to the browser. Call only after authorising.
     *
     * @param  string|null  $downloadName  Original filename to present, if known.
     */
    public static function download(?string $path, ?string $downloadName = null): StreamedResponse
    {
        abort_unless(self::exists($path), 404, 'That file is no longer on record.');

        return Storage::disk(self::DISK)->download($path, $downloadName ?: basename($path));
    }
}
