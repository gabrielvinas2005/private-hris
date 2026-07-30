<?php

namespace App\Helpers;

class NumberFormatterFallback
{
    const SPELLOUT = 0;

    private $locale;
    private $type;

    public function __construct($locale = 'en', $type = self::SPELLOUT)
    {
        $this->locale = $locale;
        $this->type = $type;
    }

    public function format($number)
    {
        if ($this->type === self::SPELLOUT) {
            return $this->spellout($number);
        }
        
        return (string) $number;
    }

    private function spellout($number)
    {
        if ($number == 0) {
            return 'zero';
        }

        $number = abs($number);
        $words = [];

        if ($number >= 1000000000) {
            $billions = floor($number / 1000000000);
            $words[] = $this->spellout($billions) . ' billion';
            $number %= 1000000000;
        }

        if ($number >= 1000000) {
            $millions = floor($number / 1000000);
            $words[] = $this->spellout($millions) . ' million';
            $number %= 1000000;
        }

        if ($number >= 1000) {
            $thousands = floor($number / 1000);
            if ($thousands > 1) {
                $words[] = $this->spellout($thousands) . ' thousand';
            } else {
                $words[] = 'one thousand';
            }
            $number %= 1000;
        }

        if ($number >= 100) {
            $hundreds = floor($number / 100);
            $words[] = $this->getOnes($hundreds) . ' hundred';
            $number %= 100;
        }

        if ($number > 0) {
            if ($number < 20) {
                $words[] = $this->getOnes($number);
            } else {
                $tens = floor($number / 10);
                $ones = $number % 10;
                $words[] = $this->getTens($tens);
                if ($ones > 0) {
                    $words[] = $this->getOnes($ones);
                }
            }
        }

        return implode(' ', $words);
    }

    private function getOnes($number)
    {
        $ones = [
            1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
            6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten',
            11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen',
            15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen',
            19 => 'nineteen'
        ];
        
        return $ones[$number] ?? '';
    }

    private function getTens($number)
    {
        $tens = [
            2 => 'twenty', 3 => 'thirty', 4 => 'forty', 5 => 'fifty',
            6 => 'sixty', 7 => 'seventy', 8 => 'eighty', 9 => 'ninety'
        ];
        
        return $tens[$number] ?? '';
    }
} 