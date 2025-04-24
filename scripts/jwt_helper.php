<?php
/**
 * Fonctions d'aide pour generer et verifier les tokens JWT
 */

// Cles pour l'algorithme RS512
define('PRIVATE_KEY', <<<EOD

EOD);

define('PUBLIC_KEY', <<<EOD

EOD);

/**
 * Genere un token JWT valide
 * 
 * @param array $payload Les donnees a inclure dans le token
 * @return string Le token JWT complet
 */
function generateJWT($payload) {
    // Header
    $header = [
        'alg' => 'RS512',
        'typ' => 'JWT'
    ];
    
    // Encodage du header et du payload
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
    
    // Creation de la signature
    $signature = '';
    $success = openssl_sign(
        $base64UrlHeader . '.' . $base64UrlPayload,
        $signature,
        PRIVATE_KEY,
        OPENSSL_ALGO_SHA512
    );
    
    if (!$success) {
        throw new Exception("Erreur lors de la création de la signature JWT");
    }
    
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    // Creation du JWT complet
    $jwt = $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    
    return $jwt;
}

/**
 * Verifie un token JWT et retourne son payload si valide
 * 
 * @param string $jwt Le token JWT a verifier
 * @return array|bool Le payload decode ou false si invalide
 */
function verifyJWT($jwt) {
    // Separer les parties du token
    $tokenParts = explode('.', $jwt);
    
    if (count($tokenParts) != 3) {
        return false;
    }
    
    $header = $tokenParts[0];
    $payload = $tokenParts[1];
    $signature = $tokenParts[2];
    
    // Reconstruire la signature pour verification
    $signature = str_replace(['-', '_'], ['+', '/'], $signature);
    $signature = base64_decode($signature);
    
    // Verifier la signature
    $success = openssl_verify(
        $header . '.' . $payload,
        $signature,
        PUBLIC_KEY,
        OPENSSL_ALGO_SHA512
    );
    
    if ($success !== 1) {
        return false;
    }
    
    // Decoder le payload
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
    
    // Verifier l'expiration
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}

/**
 * Recupere le token JWT depuis les cookies
 * 
 * @return string|null Le token JWT ou null s'il n'existe pas
 */
function getJWTFromCookie() {
    return isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : null;
}

/**
 * Verifie si l'utilisateur est connecte via JWT
 * 
 * @return bool True si l'utilisateur est connecte, false sinon
 */
function isUserLoggedIn() {
    $token = getJWTFromCookie();
    
    if (!$token) {
        return false;
    }
    
    $payload = verifyJWT($token);
    return $payload !== false;
}

/**
 * Obtient l'email de l'utilisateur connecte
 * 
 * @return string|null L'email de l'utilisateur ou null s'il n'est pas connecte
 */
function getLoggedInUserEmail() {
    $token = getJWTFromCookie();
    
    if (!$token) {
        return null;
    }
    
    $payload = verifyJWT($token);
    
    if ($payload === false || !isset($payload['mail'])) {
        return null;
    }
    
    return $payload['mail'];
}
?>