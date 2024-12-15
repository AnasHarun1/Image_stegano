<?php
class SecureEncryption {
    // Advanced encryption method
    public static function encrypt($message, $key) {
        $salt = random_bytes(16);
        $iterations = 10000;
        
        // Key derivation using PBKDF2
        $derivedKey = hash_pbkdf2("sha256", $key, $salt, $iterations, 32, true);
        
        // Use AES-256-GCM for strong encryption
        $iv = random_bytes(16);
        $encryptedData = openssl_encrypt(
            $message, 
            'aes-256-gcm', 
            $derivedKey, 
            OPENSSL_RAW_DATA, 
            $iv, 
            $tag
        );
        
        // Combine all components
        return base64_encode($salt . $iv . $tag . $encryptedData);
    }
    
    // Advanced decryption method
    public static function decrypt($encryptedMessage, $key) {
        $decoded = base64_decode($encryptedMessage);
        
        // Extract components
        $salt = substr($decoded, 0, 16);
        $iv = substr($decoded, 16, 16);
        $tag = substr($decoded, 32, 16);
        $encryptedData = substr($decoded, 48);
        
        // Key derivation
        $iterations = 10000;
        $derivedKey = hash_pbkdf2("sha256", $key, $salt, $iterations, 32, true);
        
        // Decrypt
        $decryptedMessage = openssl_decrypt(
            $encryptedData, 
            'aes-256-gcm', 
            $derivedKey, 
            OPENSSL_RAW_DATA, 
            $iv, 
            $tag
        );
        
        return $decryptedMessage;
    }
}
?>