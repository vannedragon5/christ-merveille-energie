<?php
require_once __DIR__ . '/db.php';
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

requireAuth();

$db = getDB();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $stmt = $db->query("SELECT * FROM messages ORDER BY date_envoi DESC");
    $messages = $stmt->fetchAll();
    $total = $db->query("SELECT COUNT(*) as cnt FROM messages")->fetch()['cnt'];
    $non_lus = $db->query("SELECT COUNT(*) as cnt FROM messages WHERE lu = 0")->fetch()['cnt'];
    jsonResponse(['success' => true, 'messages' => $messages, 'total' => $total, 'non_lus' => $non_lus]);
}

if ($action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'error' => 'ID invalide.'], 400);
    $db->prepare("DELETE FROM messages WHERE id = ?")->execute([$id]);
    jsonResponse(['success' => true]);
}

if ($action === 'mark_read') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'error' => 'ID invalide.'], 400);
    $db->prepare("UPDATE messages SET lu = 1 WHERE id = ?")->execute([$id]);
    jsonResponse(['success' => true]);
}

jsonResponse(['success' => false, 'error' => 'Action inconnue.'], 400);
