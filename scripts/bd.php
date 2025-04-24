<?php
// Connexion a la base de donnees PostgreSQL
$host = "";
$port = "";
$dbname = "";
$user = "";
$password = "";

$connection_string = "host=$host port=$port dbname=$dbname user=$user password=$password";
$dbconn = pg_connect($connection_string);

if (!$dbconn) {
    echo json_encode(['success' => false, 'error' => 'Échec de connexion à la base de données']);
    exit();
}

?>