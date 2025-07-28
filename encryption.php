<?php

define('ENCRYPTION_KEY', 'mysecretkey12345'); // Must be 16/24/32 bytes
define('ENCRYPTION_METHOD', 'AES-256-CBC');
define('ENCRYPTION_IV', '1234567891011121'); // 16 bytes exactly

function encrypt_id($data)
{
    return openssl_encrypt($data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, ENCRYPTION_IV);
}

function decrypt_id($encrypted)
{
    return openssl_decrypt($encrypted, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, ENCRYPTION_IV);
}
