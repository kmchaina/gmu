<?php

namespace App\Support;

/**
 * Central definition of what may be uploaded.
 *
 * Every upload path in the system builds its validation rules from here so the
 * allowlist cannot drift between controllers. Both `extensions` (what the file
 * is called) and `mimes` (what its content looks like) are applied: a file has
 * to satisfy both, so renaming a script to .pdf does not get it through.
 *
 * Audit finding C2 — the Document Repository previously accepted any file type.
 */
final class UploadRules
{
    /** Contracts, policies, reports, certificates — office documents only. */
    public const OFFICE = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv'];

    /** General repository — office documents, images and archives. */
    public const REPOSITORY = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'zip'];

    /** 10 MB, in kilobytes — contracts, certificates, call documents. */
    public const MAX_STANDARD = 10240;

    /** 20 MB, in kilobytes — reports and repository items. */
    public const MAX_LARGE = 20480;

    /**
     * Build a validation rule string for an uploaded file.
     *
     * @param  list<string>  $extensions  One of the allowlists above.
     * @param  int  $maxKilobytes
     * @param  bool  $required
     */
    public static function for(array $extensions, int $maxKilobytes = self::MAX_STANDARD, bool $required = false): string
    {
        $list = implode(',', $extensions);

        return implode('|', [
            $required ? 'required' : 'nullable',
            'file',
            'extensions:' . $list,
            'mimes:' . $list,
            'max:' . $maxKilobytes,
        ]);
    }

    /** Office documents, optional. */
    public static function office(int $maxKilobytes = self::MAX_STANDARD): string
    {
        return self::for(self::OFFICE, $maxKilobytes);
    }

    /** Office documents, required. */
    public static function officeRequired(int $maxKilobytes = self::MAX_STANDARD): string
    {
        return self::for(self::OFFICE, $maxKilobytes, true);
    }

    /** Repository upload, required. */
    public static function repositoryRequired(int $maxKilobytes = self::MAX_LARGE): string
    {
        return self::for(self::REPOSITORY, $maxKilobytes, true);
    }
}
