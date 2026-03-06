<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Méthode non autorisée.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$nom       = trim($data['name'] ?? $data['nom'] ?? '');
$telephone = trim($data['phone'] ?? $data['telephone'] ?? '');
$message   = trim($data['message'] ?? '');

if (!$nom || !$telephone) {
    jsonResponse(['success' => false, 'error' => 'Le nom et le téléphone sont requis.'], 400);
}

// Sécurité de base
$nom       = htmlspecialchars($nom, ENT_QUOTES, 'UTF-8');
$telephone = htmlspecialchars($telephone, ENT_QUOTES, 'UTF-8');
$message   = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

$db = getDB();
$stmt = $db->prepare("INSERT INTO messages (nom, telephone, message) VALUES (?, ?, ?)");
$stmt->execute([$nom, $telephone, $message]);

jsonResponse([
    'success' => true,
    'message' => 'Votre message a bien été envoyé. Nous vous répondrons très rapidement !'
]);
