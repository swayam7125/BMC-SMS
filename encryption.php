<?php
define('ENCRYPTION_KEY', 'mysecretkey12345'); // Use secure random key
define('ENCRYPTION_METHOD', 'AES-256-CBC');
define('ENCRYPTION_IV', '1234567891011121'); // Must be 16 bytes

function encrypt_id($id) {
    return openssl_encrypt($id, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, ENCRYPTION_IV);
}

function decrypt_id($encrypted) {
    return openssl_decrypt($encrypted, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, ENCRYPTION_IV);
}
?>
