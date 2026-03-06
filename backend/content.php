<?php
require_once __DIR__ . '/db.php';
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Lecture publique du contenu
    $db = getDB();
    $stmt = $db->query("SELECT cle, valeur FROM content");
    $rows = $stmt->fetchAll();
    $content = [];
    foreach ($rows as $row) {
        $content[$row['cle']] = $row['valeur'];
    }
    jsonResponse(['success' => true, 'content' => $content]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAuth();
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (empty($data)) {
        jsonResponse(['success' => false, 'error' => 'Aucune donnée reçue.'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO content (cle, valeur) VALUES (?, ?)
                          ON CONFLICT(cle) DO UPDATE SET valeur = excluded.valeur");
    foreach ($data as $cle => $valeur) {
        $cle = preg_replace('/[^a-z0-9_]/', '', strtolower($cle));
        $stmt->execute([$cle, $valeur]);
    }
    jsonResponse(['success' => true, 'message' => 'Contenu sauvegardé avec succès.']);
}

jsonResponse(['success' => false, 'error' => 'Méthode non autorisée.'], 405);
