<?php
require_once __DIR__ . '/db.php';
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'check';

if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');

    if (!$username || !$password) {
        jsonResponse(['success' => false, 'error' => 'Champs requis manquants.']);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        jsonResponse(['success' => true, 'username' => $admin['username']]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Identifiants incorrects.'], 401);
    }
}

if ($action === 'logout') {
    session_destroy();
    jsonResponse(['success' => true]);
}

if ($action === 'check') {
    if (!empty($_SESSION['admin_id'])) {
        jsonResponse(['success' => true, 'username' => $_SESSION['admin_username']]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Non connecté.'], 401);
    }
}

jsonResponse(['success' => false, 'error' => 'Action inconnue.'], 400);
