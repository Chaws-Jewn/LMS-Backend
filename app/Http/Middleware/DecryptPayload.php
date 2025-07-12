<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class DecryptPayload
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $encrypted = $request->input('ml');

        if ($encrypted) {
            try {
                $data = $this->decryptPayload($encrypted);
                $request->merge($data);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid encrypted payload'], 400);
            }
        }

        return $next($request);
    }

    private function decryptPayload($base64Payload)
    {
        $decoded = base64_decode($base64Payload);

        $prefixHexLen = 12;
        $ivHexLen = 32;
        $keyHexLen = 64;

        $hexPayload = $decoded;

        $prefix = substr($hexPayload, 0, $prefixHexLen);
        $ivHex = substr($hexPayload, $prefixHexLen, $ivHexLen);
        $keyHex = substr($hexPayload, $prefixHexLen + $ivHexLen, $keyHexLen);
        $cipherHex = substr($hexPayload, $prefixHexLen + $ivHexLen + $keyHexLen);

        $iv = hex2bin($ivHex);
        $key = hex2bin($keyHex);
        $cipherText = hex2bin($cipherHex);

        $decrypted = openssl_decrypt(
            $cipherText,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return  json_decode($decrypted, true);;
    }
}
