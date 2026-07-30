<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class BranchHelper
{
    public static function getMainBranch(): ?object
    {
        $branch = DB::table('branches')->where('is_main_branch', 1)->first();

        if (!$branch) {
            $branch = DB::table('branches')->orderBy('id', 'asc')->first();
        }

        return $branch;
    }

    public static function getMainBranchCode(): string
    {
        $branch = self::getMainBranch();
        $code = trim($branch->code ?? '');

        return $code !== '' ? $code : 'Agency';
    }
}
