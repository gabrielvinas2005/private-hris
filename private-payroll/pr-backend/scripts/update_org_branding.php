<?php

declare(strict_types=1);

$viewsDir = __DIR__ . '/../resources/views';

$phraseReplacements = [
    'Philippine Trade Training Center (PTTC)' => '{{ $orgCompanyName }} ({{ $orgBranchCode }})',
    'Philippine Trade Training Center' => '{{ $orgCompanyName }}',
    'PHILIPPINE TRADE TRAINING CENTER' => '{{ strtoupper($orgCompanyName) }}',
    'DTI - PHILIPPINE TRADE TRAINING CENTER' => 'DTI - {{ $orgCompanyName }}',
    'DTI-PTTC' => 'DTI-{{ $orgBranchCode }}',
    'PTTC-HRMP System' => '{{ $orgBranchCode }}-HRMP System',
    'PTTC-HRMP' => '{{ $orgBranchCode }}-HRMP',
    'PTTC Bldg, Sen. Gil J. Puyat Ave cor.' => '{{ $orgCompanyAddress }}',
    'PTTC BUILDING SEN. GIL PUYAT AVE. COR ROXAS BLVD. 1300 PASAY CITY PHILIPPINES' => '{{ strtoupper($orgCompanyAddress) }}',
    'PTTC Building Sen. Gil Puyat Ave. Cor Roxas Blvd. 1300 Pasay City Philippines' => '{{ $orgCompanyAddress }}',
    'PTTC BLDG, Sen. Gil J Puyat, Ave. cor.<br> Roxas Blvd., Pasay' => '{{ $orgCompanyAddress }}',
    'Roxas Blvd., Pasay City' => '{{ $orgCompanyAddress }}',
    'Roxas Boulevard, Pasay City' => '{{ $orgCompanyAddress }}',
    'hr@pttc.gov.ph' => '{{ $orgCompanyEmail }}',
    "    \$entityName  = 'DTI - PHILIPPINE TRADE TRAINING CENTER';" => "    \$entityName  = 'DTI - ' . \$orgCompanyName;",
    "        ?? (\$company[0]->name ?? 'PHILIPPINE TRADE TRAINING CENTER');" => "        ?? \$orgCompanyName;",
];

$fallbackPatterns = [
    '/\{\{\s*\$companies\[0\]->name\s*\?\?\s*\'PHILIPPINE TRADE TRAINING CENTER\'\s*\}\}/' =>
        '{{ $orgCompanyName }}',
    '/\{\{\s*\$companies\[0\]->name\s*\?\?\s*\'Company Name\'\s*\}\}/' =>
        '{{ $orgCompanyName }}',
    '/\{\{\s*\$companies\[0\]->address\s*\?\?\s*\'Company Address\'\s*\}\}/' =>
        '{{ $orgCompanyAddress }}',
    '/\{\{\s*strtoupper\(\$company\[0\]->name\s*\?\?\s*\'PHILIPPINE TRADE TRAINING CENTER\'\)\s*\}\}/' =>
        '{{ strtoupper($orgCompanyName) }}',
    '/\{\{\s*\$company\[0\]->name\s*\?\?\s*\'PHILIPPINE TRADE TRAINING CENTER\'\s*\}\}/' =>
        '{{ $orgCompanyName }}',
    '/\{\{\s*\$company\[0\]->address\s*\?\?\s*\'PTTC BUILDING[^\'"]*\'\s*\}\}/i' =>
        '{{ $orgCompanyAddress }}',
    '/\{\{\s*strtoupper\(\$company\[0\]->address\s*\?\?\s*\'PTTC BUILDING[^\'"]*\'\)\s*\}\}/i' =>
        '{{ strtoupper($orgCompanyAddress) }}',
    '/\{\{\s*\$company\[0\]->address\s*\?\?\s*\'PTTC Building[^\'"]*\'\s*\}\}/i' =>
        '{{ $orgCompanyAddress }}',
];

function isSafeToWrite(string $original, string $updated): bool
{
    if ($updated === $original) {
        return false;
    }

    if (strlen($updated) < 100) {
        return false;
    }

    if (strlen($updated) < (int) (strlen($original) * 0.85)) {
        return false;
    }

    return str_contains($updated, '<') || str_contains($updated, '@php');
}

function processFile(string $path, array $phraseReplacements, array $fallbackPatterns): bool
{
    $original = file_get_contents($path);
    if ($original === false || $original === '') {
        return false;
    }

    $content = $original;

    foreach ($phraseReplacements as $old => $new) {
        $content = str_replace($old, $new, $content);
    }

    foreach ($fallbackPatterns as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content) ?? $content;
    }

    if (str_contains($content, 'PTTC')) {
        $content = preg_replace('/\bPTTC\b/', '{{ $orgBranchCode }}', $content) ?? $content;
    }

    if (!isSafeToWrite($original, $content)) {
        return false;
    }

    $tmp = $path . '.branding.tmp';
    file_put_contents($tmp, $content);

    if (filesize($tmp) === 0) {
        @unlink($tmp);
        return false;
    }

    rename($tmp, $path);

    return true;
}

$changed = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php' || !str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }

    if (processFile($file->getPathname(), $phraseReplacements, $fallbackPatterns)) {
        $changed[] = str_replace($viewsDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
    }
}

echo 'Updated ' . count($changed) . " file(s):\n";
foreach ($changed as $item) {
    echo "  - {$item}\n";
}

foreach ($iterator as $file) {
    if ($file->getExtension() === 'php' && filesize($file->getPathname()) === 0) {
        echo "\nFATAL empty file: {$file->getPathname()}\n";
        exit(1);
    }
}
