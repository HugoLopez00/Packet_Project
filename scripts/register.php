<?php

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

// Verifier la complexite du mot de passe (synchronisé avec le JS)
$min_length = 8;
$has_lowercase = preg_match('/[a-z]/', $plain_password);
$has_uppercase = preg_match('/[A-Z]/', $plain_password);
$has_digit = preg_match('/\d/', $plain_password);
$has_special_char = preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $plain_password);

// Verifier si le mot de passe respecte toutes les conditions
if (strlen($plain_password) < $min_length || !$has_lowercase || !$has_uppercase || !$has_digit || !$has_special_char) {
    echo json_encode(['success' => false, 'error' => 'Le mot de passe doit contenir au moins 8 caractères avec au moins une minuscule, une majuscule, un chiffre et un caractère spécial']);
    exit();
}

// Verifier si l'email existe deja
$check_query = "SELECT mail FROM \"utilisateur\" WHERE mail = $1";
$check_result = pg_query_params($dbconn, $check_query, array($mail));

if (pg_num_rows($check_result) > 0) {
    echo json_encode(['success' => false, 'error' => 'Cet email est déjà utilisé']);
    pg_close($dbconn);
    exit();
}

// Hasher le mot de passe
$hashed_password = password_hash($plain_password, PASSWORD_BCRYPT, ['cost' => 12]);

// Inserer l'utilisateur dans la base de donnees avec une requete preparee
$insert_query = "INSERT INTO \"utilisateur\" (mail, mdp) VALUES ($1, $2)";
$insert_result = pg_query_params($dbconn, $insert_query, array($mail, $hashed_password));

if ($insert_result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la création du compte']);
}

pg_close($dbconn);
?>