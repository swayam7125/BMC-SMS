<?php
// IMPORTANT: Replace this with the secure key you generated.
// This key MUST be 32 characters long for AES-256-CBC.
define('ENCRYPTION_KEY', 'p4s8v/B?E(H+KbPeShVmYq3t6w9z$C&F');

define('ENCRYPTION_METHOD', 'AES-256-CBC');

/**
 * Encrypts data using a secure, randomized IV.
 * @param string $data The plaintext data to encrypt.
 * @return string The base64 encoded IV + ciphertext.
 */
function encrypt_id($data)
{
    $iv_length = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    $iv = openssl_random_pseudo_bytes($iv_length);
    $encrypted = openssl_encrypt($data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypts data that was encrypted with the encrypt_id function.
 * @param string $encrypted The base64 encoded string (IV + ciphertext).
 * @return string|false The original plaintext data, or false on failure.
 */
function decrypt_id($encrypted)
{
    $data = base64_decode($encrypted);
    if ($data === false) {
        return false;
    }

    $iv_length = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    $iv = substr($data, 0, $iv_length);
    $ciphertext = substr($data, $iv_length);
    return openssl_decrypt($ciphertext, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
}
