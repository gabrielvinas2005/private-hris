<?php

$viewsDir = __DIR__ . '/../resources/views';

$phraseReplacements = [
    'Department of Trade and Industry – Philippine Trade Training Center (PTTC),' =>
        'Department of Trade and Industry – {{ $orgCompanyName }} ({{ $orgBranchCode }}),',
    'Department of Trade and Industry - Philippine Trade Training Center (PTTC GMEA).' =>
        'Department of Trade and Industry - {{ $orgCompanyName }} ({{ $orgBranchCode }}).',
    'Philippine Trade Training Center (PTTC) - GMEA' =>
        '{{ $orgCompanyName }} ({{ $orgBranchCode }})',
    'Philippine Trade Training Center (PTTC)- Global MSME Academy Building, Sen. Gil J. Puyat Ave.' . "\n        cor. Roxas Blvd., Pasay City" =>
        '{{ $orgCompanyName }} ({{ $orgBranchCode }}), {{ $orgCompanyAddress }}',
    'Philippine Trade Training Center (PTTC) Building, Sen. Gil J. Puyat Ave. cor. Roxas Blvd., Pasay City' =>
        '{{ $orgCompanyName }} ({{ $orgBranchCode }}), {{ $orgCompanyAddress }}',
    'Philippine Trade Training Center – Global MSME Academy' => '{{ $orgCompanyName }}',
    'Philippine Trade Training Center - Global MSME Academy' => '{{ $orgCompanyName }}',
    'Philippine Trade Training Center (PTTC), an attached agency of the Department of Trade and Industry (DTI).' =>
        '{{ $orgCompanyName }} ({{ $orgBranchCode }}), an attached agency of the Department of Trade and Industry (DTI).',
    'Philippine Trade Training Center (PTTC),' => '{{ $orgCompanyName }} ({{ $orgBranchCode }}),',
    'Philippine Trade Training Center (PTTC)' => '{{ $orgCompanyName }} ({{ $orgBranchCode }})',
    'Philippine Trade Training Center-DTI:' => '{{ $orgCompanyName }}:',
    'Philippine Trade Training Center-DTI' => '{{ $orgCompanyName }}',
    'DTI Philippine Trade Training Center - Global MSME Academy!' => 'DTI {{ $orgCompanyName }}!',
    'DTI – Philippine Trade Training Center!' => 'DTI – {{ $orgCompanyName }}!',
    'PHILIPPINE TRADE TRAINING CENTER' => '{{ strtoupper($orgCompanyName) }}',
    'Philippine Trade Training Center' => '{{ $orgCompanyName }}',
    'DTI-PTTC' => 'DTI-{{ $orgBranchCode }}',
    'PTTC-GMEA' => '{{ $orgBranchCode }}-GMEA',
    'PTTC-AFMD' => '{{ $orgBranchCode }}-AFMD',
    'PTTC OED' => '{{ $orgBranchCode }} OED',
    'PTTC Team' => '{{ $orgBranchCode }} Team',
    "PTTC's" => "{{ $orgBranchCode }}'s",
    'the PTTC' => 'the {{ $orgBranchCode }}',
    'at the PTTC' => 'at the {{ $orgBranchCode }}',
    'in PTTC' => 'in {{ $orgBranchCode }}',
    'hr@pttc.gov.ph' => '{{ $orgCompanyEmail }}',
    '("PTTC")' => '("{{ $orgBranchCode }}")',
];

$addressReplacements = [
    'PTTC Building Sen. Gil Puyat Ave. Cor Roxas Blvd. 1300 Pasay City Philippines',
    'PTTC Building Sen. Gil Puyat Ave. Cor Roxas Blvd. 1300 Pasay City Pilipinas',
    'PTTC Building Sen. Gil Puyat Ave. Cor Roxas Blvd. 1300 Pasay City Philippines.',
    'PTTC Building Sen. Gil Puyat Ave. Cor Roxas Blvd. 1300 ',
    'PTTC BLDG, Sen. Gil J Puyat, Ave. cor.<br> Roxas Blvd., Pasay City.',
    'PTTC BLDG, Sen. Gil J Puyat, Ave. cor.<br> Roxas Blvd., Pasay',
    'PTTC BLDG, Sen. Gil J Puyat, Ave. cor.<br> Roxas Blvd., Pasay City',
    "PTTC Bldg., Sen. Gil Puyat Avenue, cor. Roxas Blvd.\n            1300 Pasay City",
    'Roxas Boulevard, Pasay City',
    'Roxas Blvd. cor. Gil J. Puyat Ave.',
    'SEN. GIL PUYAT cor. ROXAS BLVD., PASAY CITY',
    'Pasay City Philippines',
    'Pasay City, Philippines.',
    'Pasay City, Philippines',
    'at Pasay City, Philippines',
];

$fallbackPatterns = [
    '/\{\{\s*\$companies\[0\]->name\s*\?\?\s*\'Philippine Trade Training Center \(PTTC\) – GMEA\'\s*\}\}/' =>
        '{{ $orgCompanyName }}',
    '/\{\{\s*\$companies\[0\]->name\s*\?\?\s*\'Philippine Trade Training Center\'\s*\}\}/' =>
        '{{ $orgCompanyName }}',
    '/\{\{\s*\$positionData\[\'office\'\]\s*\?\?\s*\'PHILIPPINE TRADE TRAINING CENTER\'\s*\}\}/' =>
        "{{ \$positionData['office'] ?? strtoupper(\$orgCompanyName) }}",
    '/\{\{\s*\$positionData\[\'location\'\]\s*\?\?\s*\'SEN\. GIL PUYAT cor\. ROXAS BLVD\., PASAY CITY\'\s*\}\}/' =>
        "{{ \$positionData['location'] ?? strtoupper(\$orgCompanyAddress) }}",
];

function processFile(string $path, array $phraseReplacements, array $addressReplacements, array $fallbackPatterns): bool
{
    $original = file_get_contents($path);
    $content = $original;

    foreach ($phraseReplacements as $old => $new) {
        $content = str_replace($old, $new, $content);
    }

    foreach ($addressReplacements as $old) {
        $content = str_replace($old, '{{ $orgCompanyAddress }}', $content);
    }

    foreach ($fallbackPatterns as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }

    if (str_contains($content, 'PTTC')) {
        $content = preg_replace('/\bPTTC\b/', '{{ $orgBranchCode }}', $content);
    }

    $content = str_replace(
        '{{ $orgCompanyName }} ({{ $orgBranchCode }}) ("{{ $orgBranchCode }}")',
        '{{ $orgCompanyName }} ("{{ $orgBranchCode }}")',
        $content
    );

    $content = str_replace(
        'with office address at the {{ $orgBranchCode }} Bldg., {{ $orgCompanyAddress }}',
        'with office address at {{ $orgCompanyAddress }}',
        $content
    );

    // Fix offboarding multiline address fallback
    $content = preg_replace(
        '/\{\{\s*\$companies\[0\]->address\s*\?\?\s*\'[^\']*\'/s',
        '{{ $orgCompanyAddress',
        $content
    );

    if ($content !== $original) {
        file_put_contents($path, $content);
        return true;
    }

    return false;
}

$changed = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php' || !str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }

    if (processFile($file->getPathname(), $phraseReplacements, $addressReplacements, $fallbackPatterns)) {
        $changed[] = str_replace($viewsDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
    }
}

echo 'Updated ' . count($changed) . " file(s):\n";
foreach ($changed as $item) {
    echo "  - {$item}\n";
}
