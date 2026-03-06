<?php
require_once __DIR__ . '/db.php';
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');


if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    requireAuth();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') !== 'delete') {
    $type = $_POST['type'] ?? ''; // 'image' ou 'video'
    
    if (!in_array($type, ['image', 'video'])) {
        jsonResponse(['success' => false, 'error' => 'Type de fichier invalide.'], 400);
    }

    if (empty($_FILES['file'])) {
        jsonResponse(['success' => false, 'error' => 'Aucun fichier reçu.'], 400);
    }

    $file = $_FILES['file'];
    $allowedImages = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $allowedVideos = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
    $allowed = $type === 'image' ? $allowedImages : $allowedVideos;

    if (!in_array($file['type'], $allowed)) {
        jsonResponse(['success' => false, 'error' => 'Format de fichier non supporté.'], 400);
    }

    $maxSize = $type === 'video' ? 200 * 1024 * 1024 : 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        $limit = $type === 'video' ? '200 Mo' : '10 Mo';
        jsonResponse(['success' => false, 'error' => "Fichier trop grand (max $limit)."], 400);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nomFichier = uniqid('cme_', true) . '.' . $ext;
    $dossier = $type === 'image' ? UPLOADS_IMAGES : UPLOADS_VIDEOS;

    if (!is_dir($dossier)) {
        mkdir($dossier, 0755, true);
    }

    $dest = $dossier . $nomFichier;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonResponse(['success' => false, 'error' => "Erreur lors de l'enregistrement du fichier."], 500);
    }

    $chemin = 'uploads/' . ($type === 'image' ? 'images' : 'videos') . '/' . $nomFichier;
    $db = getDB();
    $db->prepare("INSERT INTO medias (nom_fichier, type, chemin) VALUES (?, ?, ?)")
       ->execute([$nomFichier, $type, $chemin]);

    $id = $db->lastInsertId();
    jsonResponse(['success' => true, 'id' => $id, 'chemin' => $chemin, 'nom' => $nomFichier]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = $_GET['type'] ?? null;
    $db = getDB();
    if ($type) {
        $stmt = $db->prepare("SELECT * FROM medias WHERE type = ? ORDER BY date_upload DESC");
        $stmt->execute([$type]);
    } else {
        $stmt = $db->query("SELECT * FROM medias ORDER BY date_upload DESC");
    }
    jsonResponse(['success' => true, 'medias' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'delete')) {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'error' => 'ID invalide.'], 400);
    $db = getDB();
    $media = $db->prepare("SELECT * FROM medias WHERE id = ?");
    $media->execute([$id]);
    $m = $media->fetch();
    if ($m) {
        $fullPath = __DIR__ . '/../' . $m['chemin'];
        if (file_exists($fullPath)) @unlink($fullPath);
        $db->prepare("DELETE FROM medias WHERE id = ?")->execute([$id]);
    }
    jsonResponse(['success' => true]);
}

jsonResponse(['success' => false, 'error' => 'Méthode non autorisée.'], 405);
