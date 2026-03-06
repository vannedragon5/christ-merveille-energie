<?php
require_once __DIR__ . '/db.php';
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

if ($method === 'GET') {
    $stmt = $db->query("SELECT * FROM services ORDER BY ordre ASC, id ASC");
    $services = $stmt->fetchAll();
    jsonResponse(['success' => true, 'services' => $services]);
}

if ($method === 'POST') {
    requireAuth();
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['action'])) {
        jsonResponse(['success' => false, 'error' => 'Action manquante.'], 400);
    }

    if ($data['action'] === 'add') {
        $stmt = $db->prepare("INSERT INTO services (titre, description, icon, note, ordre) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['titre'] ?? 'Nouveau Service',
            $data['description'] ?? '',
            $data['icon'] ?? 'fas fa-bolt',
            $data['note'] ?? '',
            (int)($data['ordre'] ?? 0)
        ]);
        jsonResponse(['success' => true, 'message' => 'Service ajouté.']);
    }

    if ($data['action'] === 'update') {
        if (!isset($data['id'])) jsonResponse(['success' => false, 'error' => 'ID manquant.'], 400);
        $stmt = $db->prepare("UPDATE services SET titre = ?, description = ?, icon = ?, note = ?, ordre = ? WHERE id = ?");
        $stmt->execute([
            $data['titre'],
            $data['description'],
            $data['icon'],
            $data['note'],
            (int)$data['ordre'],
            $data['id']
        ]);
        jsonResponse(['success' => true, 'message' => 'Service mis à jour.']);
    }

    if ($data['action'] === 'delete') {
        if (!isset($data['id'])) jsonResponse(['success' => false, 'error' => 'ID manquant.'], 400);
        $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$data['id']]);
        jsonResponse(['success' => true, 'message' => 'Service supprimé.']);
    }
}

jsonResponse(['success' => false, 'error' => 'Méthode non autorisée.'], 405);
