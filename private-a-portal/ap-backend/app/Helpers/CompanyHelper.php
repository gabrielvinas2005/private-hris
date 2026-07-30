<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class CompanyHelper
{
    private static ?object $company = null;

    public static function getCompany(): ?object
    {
        if (self::$company === null) {
            self::$company = DB::table('companies')->orderBy('id', 'asc')->first();
        }

        return self::$company;
    }

    public static function getName(): string
    {
        return trim(self::getCompany()->name ?? '');
    }

    public static function getAddress(): string
    {
        return trim(self::getCompany()->address ?? '');
    }

    public static function getEmail(): string
    {
        return trim(self::getCompany()->email ?? '');
    }
}
