<?php
define('DB_PATH', __DIR__ . '/database.sqlite');
define('UPLOADS_IMAGES', __DIR__ . '/../uploads/images/');
define('UPLOADS_VIDEOS', __DIR__ . '/../uploads/videos/');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        initDB($pdo);
    }
    return $pdo;
}

function initDB(PDO $pdo): void {
    $pdo->exec("PRAGMA journal_mode=WAL;");

    // Messages du formulaire de contact
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom TEXT NOT NULL,
        telephone TEXT NOT NULL,
        message TEXT,
        date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
        lu INTEGER DEFAULT 0
    )");

    // Contenu éditable de la page
    $pdo->exec("CREATE TABLE IF NOT EXISTS content (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        cle TEXT UNIQUE NOT NULL,
        valeur TEXT
    )");

    // Médias uploadés
    $pdo->exec("CREATE TABLE IF NOT EXISTS medias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom_fichier TEXT NOT NULL,
        type TEXT NOT NULL,
        chemin TEXT NOT NULL,
        date_upload DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Services
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titre TEXT NOT NULL,
        description TEXT NOT NULL,
        icon TEXT DEFAULT 'fas fa-bolt',
        note TEXT,
        ordre INTEGER DEFAULT 0
    )");

    // Admin users
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL
    )");

    // Créer le compte admin par défaut (admin / admin123)
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = 'admin'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES ('admin', ?)");
        $ins->execute([$hash]);
    }

    // Contenu par défaut si vide
    $defaults = [
        'hero_titre'        => "Christ Merveille Energie\nPhotovoltaïque & Électricité Bâtiment",
        'hero_sous_titre'   => "Solutions solaires et travaux électriques fiables et professionnels.",
        'hero_description'  => "Nous accompagnons les particuliers et les entreprises dans l'installation solaire photovoltaïque et les travaux d'électricité bâtiment. Travail bien fait, sécurité et respect des délais.",
        'whatsapp_numero'   => "2290166874093",
        'telephone'         => "+229 01 66 87 40 93 / 01 65 26 22 88 / 01 44 21 29 82",
        'email'             => "finangnonmariano@gmail.com",
        'apropos_titre'     => "Une expérience solide au service de vos projets.",
        'apropos_texte1'    => "Bienvenue chez Christ Merveille Energie. Je suis professionnel en photovoltaïque et en électricité bâtiment, avec une expérience pratique dans les installations solaires et les travaux électriques.",
        'apropos_texte2'    => "J'accompagne les particuliers et les entreprises dans leurs projets, depuis l'étude jusqu'à la réalisation. Mon objectif est de proposer des solutions fiables, économiques et adaptées aux besoins de chaque client.",
        'services_titre'     => "Nos Services",
        'services_sous_titre' => "Des solutions complètes pour tous vos besoins énergétiques.",
        'footer_texte'      => "Photovoltaïque et électricité bâtiment, sérieux et fiable.",
    ];

    foreach ($defaults as $cle => $valeur) {
        $chk = $pdo->prepare("SELECT id FROM content WHERE cle = ?");
        $chk->execute([$cle]);
        if (!$chk->fetch()) {
            $ins = $pdo->prepare("INSERT INTO content (cle, valeur) VALUES (?, ?)");
            $ins->execute([$cle, $valeur]);
        }
    }

    // Services par défaut si vide
    $stmt = $pdo->query("SELECT COUNT(*) FROM services");
    if ($stmt->fetchColumn() == 0) {
        $servs = [
            ['Installation Solaire', 'Produisez votre propre électricité grâce au soleil. Nous installons vos panneaux photovoltaïques clés en main pour réduire vos factures et gagner en autonomie énergétique.', 'fas fa-solar-panel', 'Chaque projet est étudié avec soin afin de vous proposer la solution la plus adaptée à vos besoins et à votre budget.', 1],
            ['Formation Photovoltaïque', 'Vous souhaitez maîtriser le solaire ? Nous proposons des formations pratiques en photovoltaïque, orientées terrain, pour étudiants, techniciens et futurs installateurs.', 'fas fa-chalkboard-teacher', 'Chaque projet est étudié avec soin afin de vous proposer la solution la plus adaptée à vos besoins et à votre budget.', 2],
            ['Électricité Bâtiment', 'Installation électrique complète pour maisons et bureaux. Mise aux normes, câblage, éclairage et dépannage rapide. Nous assurons la sécurité de votre réseau.', 'fas fa-bolt', 'Chaque projet est étudié avec soin afin de vous proposer la solution la plus adaptée à vos besoins et à votre budget.', 3],
        ];
        $ins = $pdo->prepare("INSERT INTO services (titre, description, icon, note, ordre) VALUES (?, ?, ?, ?, ?)");
        foreach ($servs as $s) {
            $ins->execute($s);
        }
    }
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function requireAuth(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_id'])) {
        jsonResponse(['success' => false, 'error' => 'Non autorisé'], 401);
    }
}
