<?

namespace App\Services;

class WTIEncryptService
{
    protected $key = '00000000000000000000000000000000';

    public function mcrypt_decrypt(string $param): String
    {
        $ivCiphertext  = base64_decode($param);
        $iv = substr($ivCiphertext, 0, 16);
        $ciphertext = substr($ivCiphertext, 16);

        if (!$ciphertext) {
            $param = env($param);

            $ivCiphertext  = base64_decode($param);
            $iv = substr($ivCiphertext, 0, 16);
            $ciphertext = substr($ivCiphertext, 16);

            $value = openssl_decrypt($ciphertext, "aes-256-cbc", $this->key, OPENSSL_RAW_DATA, $iv);
        } else {
            $value = openssl_decrypt($ciphertext, "aes-256-cbc", $this->key, OPENSSL_RAW_DATA, $iv);
        }

        return $value;
    }
}
