<?php

if (!function_exists('discover_eportal_public_storage_paths')) {
    /**
     * Find every sibling ep-backend/storage/app/public near the payroll install (any parent folder name).
     * Prefers e_portal/ when multiple siblings exist at the same level.
     */
    function discover_eportal_public_storage_paths(): array
    {
        $epPublic = 'ep-backend' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'public';
        $found = [];

        $dir = realpath(base_path());
        if ($dir === false) {
            return [];
        }

        for ($depth = 0; $depth < 6 && $dir !== false; $depth++) {
            $direct = $dir . DIRECTORY_SEPARATOR . $epPublic;
            if (is_dir($direct)) {
                $found[] = realpath($direct) ?: $direct;
            }

            $nestedByName = [];
            foreach (scandir($dir) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $child = $dir . DIRECTORY_SEPARATOR . $entry;
                if (!is_dir($child)) {
                    continue;
                }
                $nested = $child . DIRECTORY_SEPARATOR . $epPublic;
                if (is_dir($nested)) {
                    $nestedByName[$entry] = realpath($nested) ?: $nested;
                }
            }

            foreach (['e_portal', 'PTTC-Eportal', 'e-portal', 'eportal'] as $preferred) {
                if (isset($nestedByName[$preferred])) {
                    $found[] = $nestedByName[$preferred];
                    unset($nestedByName[$preferred]);
                }
            }
            foreach ($nestedByName as $path) {
                $found[] = $path;
            }

            $parent = dirname($dir);
            if ($parent === $dir) {
                break;
            }
            $dir = $parent;
        }

        return array_values(array_unique(array_filter($found)));
    }
}

if (!function_exists('discover_eportal_public_storage')) {
    function discover_eportal_public_storage(): ?string
    {
        $paths = discover_eportal_public_storage_paths();

        return $paths[0] ?? null;
    }
}

$eportalStorageRoot = env('EPORTAL_STORAGE_ROOT');
$eportalStorageRoots = $eportalStorageRoot
    ? [rtrim((string) $eportalStorageRoot, '/\\')]
    : discover_eportal_public_storage_paths();

return [
    /*
    |--------------------------------------------------------------------------
    | Attachments database (non_dtr_attachments.file_content)
    |--------------------------------------------------------------------------
    |
    | COS accomplishment files are stored in WTIHRIS_PTTC_ATTACHMENTS.dbo.non_dtr_attachments
    | (see config/database.php connections.attachments).
    |
    */
    'attachments_connection' => env('ATTACHMENTS_DB_CONNECTION', 'attachments'),
    'attachments_table' => env('ATTACHMENTS_TABLE', 'non_dtr_attachments'),

    /*
    |--------------------------------------------------------------------------
    | E-Portal public storage (legacy non-DTR uploads on disk)
    |--------------------------------------------------------------------------
    |
    | COS task attachments are uploaded via E-Portal into:
    | ep-backend/storage/app/public/non_dtr_attachments/
    |
    | Auto-discovered from the payroll install path (parent folder name may vary).
    | Payroll and E-Portal must run from the same deployment root, e.g.:
    |   {deploy}/e_portal/ep-backend/...
    |   {deploy}/payroll/pr-backend/...
    |
    | Override only when E-Portal storage is on another path or machine:
    | EPORTAL_STORAGE_ROOT=/path/to/ep-backend/storage/app/public
    |
    */
    'eportal_storage_root' => $eportalStorageRoots[0] ?? null,
    'eportal_storage_roots' => $eportalStorageRoots,

    /*
    |--------------------------------------------------------------------------
    | HRIS shared storage root (legacy / override)
    |--------------------------------------------------------------------------
    */
    'hris_storage_root' => env('HRIS_STORAGE_ROOT'),

    /*
    |--------------------------------------------------------------------------
    | E-Portal / HRIS API base URL (optional proxy)
    |--------------------------------------------------------------------------
    */
    'hris_api_url' => env('HRIS_API_URL', 'http://localhost:8000/api'),
];
