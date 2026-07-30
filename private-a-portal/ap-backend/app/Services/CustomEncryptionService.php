<?php

namespace App\Services;

class CustomEncryptionService
{
    private $shift;
    
    public function __construct($shift = 200)
    {
        $this->shift = $shift; // Same shift value as your SQL Server function
    }
    
    /**
     * Encrypt text using character shift (same logic as SQL Server ufn_encrypt)
     */
    public function encrypt($input)
    {
        if (empty($input) || $input === null) {
            return null;
        }
        
        $output = '';
        $length = mb_strlen($input, 'UTF-8');
        
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($input, $i, 1, 'UTF-8');
            $asciiVal = $this->getUnicodeValue($char);
            
            // Apply character shift (same as SQL Server logic)
            $shifted = $asciiVal + $this->shift;
            
            // Handle overflow (same as SQL Server logic)
            if ($shifted > 65535) {
                $shifted = $shifted - 65535;
            }
            
            $output .= $this->getCharFromUnicode($shifted);
        }
        
        return $output;
    }
    
    /**
     * Decrypt text using reverse character shift (same logic as SQL Server ufn_decrypt)
     */
    public function decrypt($encrypted)
    {
        if (empty($encrypted) || $encrypted === null) {
            return null;
        }
        
        $output = '';
        $length = mb_strlen($encrypted, 'UTF-8');
        
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($encrypted, $i, 1, 'UTF-8');
            $asciiVal = $this->getUnicodeValue($char);
            
            // Reverse the character shift
            $shifted = $asciiVal - $this->shift;
            
            // Handle underflow (same as SQL Server logic)
            if ($shifted < 0) {
                $shifted = $shifted + 65535;
            }
            
            $output .= $this->getCharFromUnicode($shifted);
        }
        
        return $output;
    }
    
    /**
     * Get Unicode value of character (equivalent to SQL Server UNICODE function)
     */
    private function getUnicodeValue($char)
    {
        $values = unpack('N*', mb_convert_encoding($char, 'UCS-4BE', 'UTF-8'));
        return $values[1] ?? 0;
    }
    
    /**
     * Get character from Unicode value (equivalent to SQL Server NCHAR function)
     */
    private function getCharFromUnicode($unicodeValue)
    {
        return mb_convert_encoding(pack('N', $unicodeValue), 'UTF-8', 'UCS-4BE');
    }
}
