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
        $company = self::getCompany();

        return trim($company->name ?? '');
    }

    public static function getAddress(): string
    {
        $company = self::getCompany();

        return trim($company->address ?? '');
    }

    public static function getEmail(): string
    {
        $company = self::getCompany();

        return trim($company->email ?? '');
    }

    public static function getTelephone(): string
    {
        $company = self::getCompany();

        return trim($company->telephone_no ?? '');
    }

    public static function getMobile(): string
    {
        $company = self::getCompany();

        return trim($company->mobile_no ?? '');
    }

    public static function getContactPhone(): string
    {
        $telephone = self::getTelephone();
        $mobile = self::getMobile();

        if ($telephone !== '' && $mobile !== '') {
            return "{$telephone} / {$mobile}";
        }

        return $telephone !== '' ? $telephone : $mobile;
    }

    public static function getLogoBase64(): ?string
    {
        $company = self::getCompany();
        $logo = $company->logo ?? null;

        return !empty($logo) ? $logo : null;
    }
}
