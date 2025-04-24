<?php
// Fichier Handler pour router les requetes vers register.php ou login.php

// Autoriser les requetes CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Gerer les requetes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Determiner si la requete provient de register.html ou login.html
$isLogin = false;

// Verifier le referer pour determiner la source
if (isset($_SERVER['HTTP_REFERER'])) {
    if (strpos($_SERVER['HTTP_REFERER'], 'login.html') !== false) {
        $isLogin = true;
    }
}

// Si pas de referer clair, verifier les donnees JSON
if (!isset($isLogin)) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (isset($data['action']) && $data['action'] === 'login') {
        $isLogin = true;
    }
}

// Router vers le script approprie
if ($isLogin) {
    require_once __DIR__ . '/../scripts/login.php';
} else {
    require_once __DIR__ . '/../scripts/register.php';
}
?>