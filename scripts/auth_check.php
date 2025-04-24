<?php
/**
 * Ce fichier verifie si l'utilisateur est connecte via JWT
 * Redirige vers login.html si l'utilisateur n'est pas connecte
 */

// Inclure l'aide JWT
require_once __DIR__ . '/jwt_helper.php';

/**
 * Verifie l'authentification et redirige si necessaire
 * 
 * @param bool $redirect Si true, redirige vers login.html si non authentifie
 * @return bool True si authentifie, false sinon
 */
function checkAuth($redirect = true) {
    // Verifier si l'utilisateur est connecte
    if (!isUserLoggedIn()) {
        if ($redirect) { 
             // Rediriger vers la page de connexion
            header("Location: /login.html");
            exit;
        }
        return false;
    }
    return true;
}



// Si ce fichier est appele directement, verifier l'authentification
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    // Format de reponse JSON pour les requetes AJAX
    if (!checkAuth(false)) {
        header('Content-Type: application/json');
        echo json_encode(['authenticated' => false]);
    } else { 
        header('Content-Type: text/html');
        readfile('/var/www/html/index.html');
    }
}

?>