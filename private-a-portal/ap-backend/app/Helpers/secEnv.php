<?php
if (! function_exists('secEnv')) {
    function secEnv(string $key, $default = null)
    {
        return env($key, $default);
    }
}
