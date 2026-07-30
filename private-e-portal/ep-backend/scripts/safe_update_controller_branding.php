<?php

declare(strict_types=1);

/**
 * Safely replace hardcoded PTTC / company branding in controllers & notifications.
 * Refuses to write if output would be truncated or invalid PHP.
 */

$targets = array_merge(
    glob(__DIR__ . '/../app/Http/Controllers/*.php') ?: [],
    glob(__DIR__ . '/../app/Notifications/*.php') ?: []
);

$markerFiles = [
    'Philippine Trade Training Center',
    'PHILIPPINE TRADE TRAINING CENTER',
    'PTTC',
    'hr@pttc.gov.ph',
    'Gil Puyat',
    'Roxas Blvd',
    'Pasay City',
];

$replacements = [
    // --- EP OB specific ---
    "PTTC BLDG, Sen. Gil J Puyat, Ave. cor.' . \"\\n\" . 'Roxas Blvd., Pasay City" =>
        "CompanyHelper::getAddress()",

    " of the DTI – Philippine Trade Training Center to travel to " =>
        " of the DTI – ' . CompanyHelper::getName() . ' to travel to ",

    "PTTC shall shoulder the incidental expenses and applicable per diem charged to PTTC Trust Fund subject to applicable government rules and regulations." =>
        "BranchHelper::getMainBranchCode() . ' shall shoulder the incidental expenses and applicable per diem charged to ' . BranchHelper::getMainBranchCode() . ' Trust Fund subject to applicable government rules and regulations.'",

    // --- Longest / most specific first ---
    "The Department of Trade and Industry – Philippine Trade Training Center (PTTC), with office address at the PTTC Bldg., Sen. Gil Puyat Avenue, cor. Roxas Blvd. 1300 Pasay City represented in this act by " =>
        "'The Department of Trade and Industry – ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . '), with office address at ' . CompanyHelper::getAddress() . ' represented in this act by '",

    " of the Department of Trade and Industry - Philippine Trade Training Center (PTTC GMEA)." =>
        " of the Department of Trade and Industry - ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . ').",

    "Greetings from the DTI – Philippine Trade Training Center – Global MSME Academy!" =>
        "'Greetings from the DTI – ' . CompanyHelper::getName() . '!'",

    "Greetings from the DTI Philippine Trade Training Center - Global MSME Academy!" =>
        "'Greetings from the DTI ' . CompanyHelper::getName() . '!'",

    "Warm greetings from the DTI – Philippine Trade Training Center!" =>
        "'Warm greetings from the DTI – ' . CompanyHelper::getName() . '!'",

    " the Head of Agency of the Philippine Trade Training Center – Global MSME Academy has accepted you to the said position." =>
        " the Head of Agency of ' . CompanyHelper::getName() . ' has accepted you to the said position.",

    " has been hired as a Contract of Service (COS) by the Philippine Trade Training Center (PTTC), an attached agency of the Department of Trade and Industry (DTI)." =>
        " has been hired as a Contract of Service (COS) by ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . '), an attached agency of the Department of Trade and Industry (DTI).",

    " of the Philippine Trade Training Center-DTI:" =>
        " of ' . CompanyHelper::getName() . ':",

    ", new employee of the Philippine Trade Training Center (PTTC), in the ATM Payroll of the Center." =>
        ", new employee of ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . '), in the ATM Payroll of the Center.",

    " of the Philippine Trade Training Center (PTTC) OR I am performing services for the Philippine Trade Training Center under a Contract of Service, and am executing this undertaking in favor of the Philippine Trade Training Center (\"PTTC\")" =>
        " of ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . ') OR I am performing services for ' . CompanyHelper::getName() . ' under a Contract of Service, and am executing this undertaking in favor of ' . CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . ')",

  "Official Endorsement from the University/School (e.g., Dean, International Studies Department) addressed to PTTC's Head of the Agency" =>
        "'Official Endorsement from the University/School (e.g., Dean, International Studies Department) addressed to ' . BranchHelper::getMainBranchCode() . \"'s Head of the Agency\"",

    "Philippine Trade Training Center (PTTC) - GMEA" =>
        "CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . ') - GMEA'",

    "Philippine Trade Training Center (PTTC)" =>
        "CompanyHelper::getName() . ' (' . BranchHelper::getMainBranchCode() . ')'",

    "Philippine Trade Training Center-DTI" =>
        "CompanyHelper::getName()",

    "Philippine Trade Training Center" =>
        "CompanyHelper::getName()",

    "PHILIPPINE TRADE TRAINING CENTER" =>
        "strtoupper(CompanyHelper::getName())",

    "PTTC Building Sen. Gil Puyat Ave. Cor Roxas Blvd. 1300 Pasay City Philippines" =>
        "CompanyHelper::getAddress()",

    "PTTC Building Sen. Gil Puyat Ave. Cor Roxas Blvd. 1300 Pasay City" =>
        "CompanyHelper::getAddress()",

    "PTTC Building Sen. Gil Puyat Ave. Cor Roxas Blvd. 1300" =>
        "CompanyHelper::getAddress()",

    "PTTC BLDG, Sen. Gil J Puyat, Ave. cor. Roxas Blvd., Pasay City" =>
        "CompanyHelper::getAddress()",

    "Roxas Boulevard, Pasay City" =>
        "CompanyHelper::getAddress()",

    "SEN. GIL PUYAT cor. ROXAS BLVD., PASAY CITY" =>
        "strtoupper(CompanyHelper::getAddress())",

    "Pasay City Philippines" =>
        "CompanyHelper::getAddress()",

    " at Pasay City, Philippines." =>
        " at ' . CompanyHelper::getAddress() . '.",

    " at Pasay City." =>
        " at ' . CompanyHelper::getAddress() . '.",

    "' at Pasay City, Philippines.'" =>
        "' at ' . CompanyHelper::getAddress() . '.'",

    "hr@pttc.gov.ph" =>
        "CompanyHelper::getEmail()",

    "PTTC-GMEA" =>
        "BranchHelper::getMainBranchCode() . '-GMEA'",

    "PTTC-AFMD" =>
        "BranchHelper::getMainBranchCode() . '-AFMD'",

    "DTI-PTTC" =>
        "'DTI-' . BranchHelper::getMainBranchCode()",

    "PTTC Team" =>
        "BranchHelper::getMainBranchCode() . ' Team'",

    "PTTC Office" =>
        "BranchHelper::getMainBranchCode() . ' Office'",

    " and hereinafter referred to as the PTTC;" =>
        " and hereinafter referred to as the ' . BranchHelper::getMainBranchCode() . ';",

    ", sa PTTC Building Sen. Gil Puyat Ave. Cor Roxas Blvd. 1300 Pasay City Philippines." =>
        ", sa ' . CompanyHelper::getAddress() . '.",

    "Congratulations and Welcome to PTTC-GMEA!" =>
        "'Congratulations and Welcome to ' . BranchHelper::getMainBranchCode() . '!'",

    " – format will be given by DTI-PTTC upon successful onboard and we would need details of the " =>
        " – format will be given by DTI-' . BranchHelper::getMainBranchCode() . ' upon successful onboard and we would need details of the ",

    ". Correspondingly, you are requested to submit the following pre-employment requirements to the PTTC-AFMD Human Resource Section on or before " =>
        ". Correspondingly, you are requested to submit the following pre-employment requirements to the ' . BranchHelper::getMainBranchCode() . '-AFMD Human Resource Section on or before ",

    ": 'Philippine Trade Training Center'" =>
        ": CompanyHelper::getName()",

    ": 'PHILIPPINE TRADE TRAINING CENTER'" =>
        ": strtoupper(CompanyHelper::getName())",

    "?? 'PHILIPPINE TRADE TRAINING CENTER'" =>
        "?? strtoupper(CompanyHelper::getName())",

    "?? 'Philippine Trade Training Center'" =>
        "?? CompanyHelper::getName()",

    "?? 'PTTC BLDG, Sen. Gil J Puyat, Ave. cor. Roxas Blvd., Pasay City'" =>
        "?? CompanyHelper::getAddress()",

    "strtoupper(\$positionData['office'] ?? 'PHILIPPINE TRADE TRAINING CENTER')" =>
        "strtoupper(\$positionData['office'] ?? CompanyHelper::getName())",

    "strtoupper(\$positionData['location'] ?? 'SEN. GIL PUYAT cor. ROXAS BLVD., PASAY CITY')" =>
        "strtoupper(\$positionData['location'] ?? CompanyHelper::getAddress())",

    ": 'PTTC'" =>
        ": CompanyHelper::getName()",
];

function needsMarker(string $content, array $markers): bool
{
    foreach ($markers as $marker) {
        if (stripos($content, $marker) !== false) {
            return true;
        }
    }

    return false;
}

function fixAddTextQuotedExpressions(string $content): string
{
  // ->addText('expr', ->addText(expr, when expr is a helper call
    return preg_replace_callback(
        "/->addText\\('((?:CompanyHelper|BranchHelper|strtoupper)\\:[^']*)'/",
        static fn (array $m) => '->addText(' . $m[1],
        $content
    ) ?? $content;
}

function fixBrokenQuoteConcat(string $content): string
{
    // Fix patterns like addText(''Greetings...  -> addText('Greetings...
    $content = str_replace("addText(''", "addText('", $content);
    $content = str_replace("addText(''Greetings", "addText('Greetings", $content);

    return $content;
}

function ensureHelperImports(string $content): string
{
    if (!str_contains($content, 'CompanyHelper::') && !str_contains($content, 'BranchHelper::')) {
        return $content;
    }

    if (str_contains($content, 'use App\\Helpers\\CompanyHelper;')) {
        return $content;
    }

    if (preg_match('/namespace App\\\\Http\\\\Controllers;/', $content)) {
        return preg_replace(
            '/namespace App\\\\Http\\\\Controllers;\R\R/',
            "namespace App\\Http\\Controllers;\n\nuse App\\Helpers\\BranchHelper;\nuse App\\Helpers\\CompanyHelper;\n",
            $content,
            1
        ) ?? $content;
    }

    if (preg_match('/namespace App\\\\Notifications;/', $content)) {
        return preg_replace(
            '/namespace App\\\\Notifications;\R\R/',
            "namespace App\\Notifications;\n\nuse App\\Helpers\\BranchHelper;\nuse App\\Helpers\\CompanyHelper;\n",
            $content,
            1
        ) ?? $content;
    }

    return $content;
}

function replaceRemainingPttcInStrings(string $content): string
{
    // NDA and similar: "for PTTC," -> for ' . BranchHelper::getMainBranchCode() . ',
    $patterns = [
        '/\bfor PTTC\b/' => "for ' . BranchHelper::getMainBranchCode() . '",
        '/\bby PTTC\b/' => "by ' . BranchHelper::getMainBranchCode() . '",
        '/\bwith PTTC\b/' => "with ' . BranchHelper::getMainBranchCode() . '",
        '/\bfrom PTTC\b/' => "from ' . BranchHelper::getMainBranchCode() . '",
        '/\bto PTTC\b/' => "to ' . BranchHelper::getMainBranchCode() . '",
        '/\bthe PTTC\b/' => "the ' . BranchHelper::getMainBranchCode() . '",
        '/\bagainst PTTC\b/' => "against ' . BranchHelper::getMainBranchCode() . '",
        '/\bof PTTC\b/' => "of ' . BranchHelper::getMainBranchCode() . '",
        '/\bin favor of PTTC\b/' => "in favor of ' . BranchHelper::getMainBranchCode() . '",
        '/contracts with PTTC/' => "contracts with ' . BranchHelper::getMainBranchCode() . '",
        '/Information Technology of the PTTC/' => "Information Technology of the ' . BranchHelper::getMainBranchCode() . '",
        '/detriment of PTTC\b/' => "detriment of ' . BranchHelper::getMainBranchCode() . '",
        '/engagement with PTTC/' => "engagement with ' . BranchHelper::getMainBranchCode() . '",
        '/employment\/engagement with PTTC/' => "employment/engagement with ' . BranchHelper::getMainBranchCode() . '",
        '/undertaking in favor of PTTC/' => "undertaking in favor of ' . BranchHelper::getMainBranchCode() . '",
        '/requested by PTTC\b/' => "requested by ' . BranchHelper::getMainBranchCode() . '",
        '/action against PTTC\b/' => "action against ' . BranchHelper::getMainBranchCode() . '",
        '/lobby guard on duty upon entering the building/' => "lobby guard on duty upon entering the building", // unchanged
    ];

    foreach ($patterns as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content) ?? $content;
    }

    return $content;
}

function isSafeToWrite(string $original, string $updated): bool
{
    if ($updated === $original) {
        return false;
    }

    $originalLen = strlen($original);
    $updatedLen = strlen($updated);

    if ($updatedLen < 100) {
        return false;
    }

    if ($updatedLen < (int) ($originalLen * 0.85)) {
        return false;
    }

    if (!str_contains($updated, '<?php') || !str_contains($updated, 'namespace')) {
        return false;
    }

    if (!str_contains($updated, 'class ')) {
        return false;
    }

    return true;
}

$updatedFiles = [];
$skippedFiles = [];
$errors = [];

foreach ($targets as $path) {
    $original = file_get_contents($path);
    if ($original === false || $original === '') {
        continue;
    }

    if (!needsMarker($original, $markerFiles)) {
        continue;
    }

    $content = $original;
    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }

    $content = replaceRemainingPttcInStrings($content);
    $content = fixAddTextQuotedExpressions($content);
    $content = fixBrokenQuoteConcat($content);
    $content = ensureHelperImports($content);

    if (!isSafeToWrite($original, $content)) {
        $skippedFiles[] = basename($path) . ' (safety check failed)';
        continue;
    }

    $tmp = $path . '.branding.tmp';
    file_put_contents($tmp, $content);

    $lint = shell_exec('php -l ' . escapeshellarg($tmp) . ' 2>&1') ?? '';
    if (!str_contains($lint, 'No syntax errors detected')) {
        $errors[] = basename($path) . ': ' . trim($lint);
        @unlink($tmp);
        continue;
    }

    if (!rename($tmp, $path)) {
        $errors[] = basename($path) . ': failed to replace file';
        @unlink($tmp);
        continue;
    }

    $updatedFiles[] = basename($path);
}

echo 'Updated ' . count($updatedFiles) . " file(s):\n";
foreach ($updatedFiles as $file) {
    echo "  - {$file}\n";
}

if ($skippedFiles !== []) {
    echo "\nSkipped (safety):\n";
    foreach ($skippedFiles as $file) {
        echo "  - {$file}\n";
    }
}

if ($errors !== []) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    exit(1);
}

// Final sanity: no empty controller files
foreach ($targets as $path) {
    if (filesize($path) === 0) {
        echo "\nFATAL: {$path} is empty!\n";
        exit(1);
    }
}

echo "\nAll target files non-empty. Done.\n";
