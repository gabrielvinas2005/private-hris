<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Crypt;

class EncryptionHelper
{
    /**
     * Encrypt a string value
     *
     * @param string|null $value
     * @return string|null
     */
    public static function encrypt($value)
    {
        if (empty($value)) {
            return $value;
        }
        
        return Crypt::encryptString($value);
    }

    /**
     * Decrypt a string value
     *
     * @param string|null $value
     * @return string|null
     */
    public static function decrypt($value)
    {
        if (empty($value)) {
            return $value;
        }
        
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            // If decryption fails, return the original value (might be already decrypted)
            return $value;
        }
    }

    /**
     * Encrypt family background fields
     *
     * @param array $data
     * @return array
     */
    public static function encryptFamilyFields(array $data): array
    {
        $fieldsToEncrypt = [
            'father_first_name',
            'father_middle_name', 
            'father_last_name',
            'mother_first_name',
            'mother_middle_name',
            'mother_last_name',
            'spouse_first_name',
            'spouse_middle_name',
            'spouse_last_name'
        ];

        foreach ($fieldsToEncrypt as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = self::encrypt($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Decrypt family background fields
     *
     * @param array $data
     * @return array
     */
    public static function decryptFamilyFields(array $data): array
    {
        $fieldsToDecrypt = [
            'father_first_name',
            'father_middle_name', 
            'father_last_name',
            'mother_first_name',
            'mother_middle_name',
            'mother_last_name',
            'spouse_first_name',
            'spouse_middle_name',
            'spouse_last_name'
        ];

        foreach ($fieldsToDecrypt as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = self::decrypt($data[$field]);
            }
        }

        return $data;
    }
}
