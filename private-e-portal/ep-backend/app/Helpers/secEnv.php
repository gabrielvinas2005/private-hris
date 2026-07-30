<?php

use App\Services\WTIEncryptService;

if (!function_exists('secEnv')) {

    /**
     * secEnv Helper function
     *
     * @param $name string
     * @param $fallback string
     *
     * @return string
     */
    function secEnv($name, $fallback = '')
    {

        $configval = (new WTIEncryptService)->mcrypt_decrypt($name);

        return isset($configval) ? $configval : $fallback;
    }
}
