<?php

namespace App\Helpers;

class NumberToWords
{
    private static $ones = [
        0 => '', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four',
        5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine',
        10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen',
        14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen',
        18 => 'eighteen', 19 => 'nineteen'
    ];

    private static $tens = [
        2 => 'twenty', 3 => 'thirty', 4 => 'forty', 5 => 'fifty',
        6 => 'sixty', 7 => 'seventy', 8 => 'eighty', 9 => 'ninety'
    ];

    private static $thousands = ['', 'thousand', 'million', 'billion', 'trillion'];

    public static function convert($number)
    {
        if ($number == 0) {
            return 'zero';
        }

        if ($number < 0) {
            return 'negative ' . self::convert(abs($number));
        }

        $words = '';
        $thousandsIndex = 0;

        while ($number > 0) {
            $chunk = $number % 1000;
            if ($chunk != 0) {
                $chunkWords = self::convertChunk($chunk);
                $words = $chunkWords . ' ' . self::$thousands[$thousandsIndex] . ' ' . $words;
            }
            $number = (int)($number / 1000);
            $thousandsIndex++;
        }

        return trim($words);
    }

    private static function convertChunk($number)
    {
        $words = '';

        if ($number >= 100) {
            $words .= self::$ones[(int)($number / 100)] . ' hundred ';
            $number %= 100;
        }

        if ($number >= 20) {
            $words .= self::$tens[(int)($number / 10)];
            if ($number % 10 > 0) {
                $words .= '-' . self::$ones[$number % 10];
            }
        } elseif ($number > 0) {
            $words .= self::$ones[$number];
        }

        return trim($words);
    }

    public static function formatCurrency($amount)
    {
        $wholePart = (int)$amount;
        $decimalPart = round(($amount - $wholePart) * 100);

        $words = self::convert($wholePart) . ' pesos';

        if ($decimalPart > 0) {
            $words .= ' and ' . self::convert($decimalPart) . ' centavos';
        }

        return ucfirst($words);
    }
} 