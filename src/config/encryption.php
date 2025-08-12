<?php
/**
 * Encryption Class for NPS Application
 * Provides secure URL encryption for campaign sharing
 */

class Encryption {
    private $key;
    private $cipher = 'AES-256-CBC';
    
    public function __construct() {
        // Usar una clave secreta desde variables de entorno o generar una
        $this->key = $_ENV['ENCRYPTION_KEY'] ?? hash('sha256', 'nps_survey_secret_key_2024', true);
    }
    
    /**
     * Encrypt campaign data for secure URL generation
     */
    public function encryptCampaignData($campaignId, $expiryHours = 168) { // 7 días por defecto
        $data = [
            'campaign_id' => $campaignId,
            'expires_at' => time() + ($expiryHours * 3600),
            'random' => bin2hex(random_bytes(8)) // Agregar aleatoriedad
        ];
        
        $jsonData = json_encode($data);
        $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
        
        $encrypted = openssl_encrypt($jsonData, $this->cipher, $this->key, 0, $iv);
        
        if ($encrypted === false) {
            throw new Exception('Error al encriptar los datos');
        }
        
        // Combinar IV y datos encriptados y codificar en base64
        $combined = $iv . $encrypted;
        return urlencode(base64_encode($combined));
    }
    
    /**
     * Decrypt campaign data from secure URL
     */
    public function decryptCampaignData($encryptedData) {
        try {
            $combined = base64_decode(urldecode($encryptedData));
            
            if ($combined === false) {
                throw new Exception('Datos encriptados inválidos');
            }
            
            $ivLength = openssl_cipher_iv_length($this->cipher);
            $iv = substr($combined, 0, $ivLength);
            $encrypted = substr($combined, $ivLength);
            
            $decrypted = openssl_decrypt($encrypted, $this->cipher, $this->key, 0, $iv);
            
            if ($decrypted === false) {
                throw new Exception('Error al desencriptar los datos');
            }
            
            $data = json_decode($decrypted, true);
            
            if (!$data || !isset($data['campaign_id']) || !isset($data['expires_at'])) {
                throw new Exception('Estructura de datos inválida');
            }
            
            // Verificar expiración
            if (time() > $data['expires_at']) {
                throw new Exception('El enlace ha expirado');
            }
            
            return $data['campaign_id'];
            
        } catch (Exception $e) {
            error_log("Error de desencriptación: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate a secure hash for additional validation
     */
    public function generateSecureHash($campaignId, $timestamp) {
        return hash_hmac('sha256', $campaignId . $timestamp, $this->key);
    }
    
    /**
     * Verify secure hash
     */
    public function verifySecureHash($campaignId, $timestamp, $hash) {
        $expectedHash = $this->generateSecureHash($campaignId, $timestamp);
        return hash_equals($expectedHash, $hash);
    }
}
