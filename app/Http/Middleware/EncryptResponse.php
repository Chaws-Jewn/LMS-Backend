<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Encryption\EncryptException;

class EncryptResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $contentType = $response->headers->get('Content-Type');

        $encryptExceptionHeader = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/pdf',
            'image/jpeg',
            'image/png'
        ];


        if ($response->isSuccessful() && !in_array($contentType, $encryptExceptionHeader)) {
            $content = $response->getContent();

            $encryptedContent = $this->encrypt($content);

            $encryptedContent = ['lm' => $encryptedContent];

            $response->setContent(json_encode($encryptedContent));
        }

        return $response;
    }


    public function encrypt($value, $serialize = true)
    {
        $key = config('app.response_key');
        $cipher = config('app.cipher');
        $iterations = 1000;
        $key_length = 32;
        $algo = "sha256";

        $salt = random_bytes(openssl_cipher_iv_length(strtolower($cipher)));
        $iv = random_bytes(openssl_cipher_iv_length(strtolower($cipher)));


        $derived_key = hash_pbkdf2($algo, $key, $salt, $iterations, $key_length, true);

        $encryptedValue = \openssl_encrypt(
            $value,
            strtolower($cipher),
            $derived_key,
            0,
            $iv
        );

        if ($encryptedValue === false) {
            throw new EncryptException('Could not encrypt the data.');
        }

        $iv = base64_encode($iv);
        $salt = base64_encode($salt);
        $iterations = base64_encode($iterations);

        $json = json_encode(compact('iv', 'encryptedValue', 'iterations', 'salt'), JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new EncryptException('Could not encrypt the data.');
        }

        return base64_encode($json);
    }
}
