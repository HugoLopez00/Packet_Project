<?php
// Inclure l'aide JWT
require_once __DIR__ . '/jwt_helper.php';
require_once __DIR__ . '/bd.php';

// Autoriser CORS si necessaire
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Pour les requetes preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verifier si c'est une requete POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Recuperer le contenu du corps de la requete
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Valider les donnees
if (!isset($data['mail']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'error' => 'Email et mot de passe requis']);
    exit();
}

// Nettoyer les entrees
$mail = filter_var($data['mail'], FILTER_SANITIZE_EMAIL);
$plain_password = $data['password'];

// Valider le format de l'email
if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Format d\'email invalide']);
    exit();
}

// Valider le domaine de l'email (@bssl.com)
$domain = substr(strrchr($mail, "@"), 1);
if (strtolower($domain) !== 'bssl.com') {
    echo json_encode(['success' => false, 'error' => 'Seuls les emails @bssl.com sont autorisés']);
    exit();
}

// Recuperer l'utilisateur depuis la base de donnees
$query = "SELECT mail, mdp FROM \"utilisateur\" WHERE mail = $1";
$result = pg_query_params($dbconn, $query, array($mail));

if (pg_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'error' => 'Email ou mot de passe incorrect']);
    pg_close($dbconn);
    exit();
}

$user = pg_fetch_assoc($result);

// Verifier le mot de passe
if (!password_verify($plain_password, $user['mdp'])) {
    echo json_encode(['success' => false, 'error' => 'Email ou mot de passe incorrect']);
    pg_close($dbconn);
    exit();
}

// Creer le payload JWT
$payload = [
    'mail' => $user['mail'],
    'exp' => time() + (60 * 60 * 24) // Expiration dans 24 heures
];

try {
    // Generer le token JWT
    $jwt = generateJWT($payload);
    
    // Definir le cookie pour le client
    $cookie_options = [
        'expires' => time() + (60 * 60 * 24),
        'path' => '/',
        'domain' => '', // Domaine vide pour correspondre au domaine actuel
        'secure' => false, // Mettre à true en production avec HTTPS
        'httponly' => true, // Empecher l'acces via JavaScript
        'samesite' => 'Lax' // Protection contre CSRF
    ];
    
    setcookie('auth_token', $jwt, $cookie_options);
    
    // Envoyer la réponse de succes
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la création du token: ' . $e->getMessage()]);
}

pg_close($dbconn);
?>