<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class BranchHelper
{
    public static function getMainBranchCode(): string
    {
        $branch = DB::table('branches')->where('is_main_branch', 1)->first()
            ?? DB::table('branches')->orderBy('id', 'asc')->first();
        $code = trim($branch->code ?? '');

        return $code !== '' ? $code : 'Agency';
    }
}
