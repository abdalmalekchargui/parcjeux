<?php
declare(strict_types=1);
require __DIR__ . '/../config/config.php';
date_default_timezone_set('Africa/Casablanca');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
session_start();

// Connexion PDO sécurisée (requêtes préparées, pas d'émulation)
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $ex) {
    http_response_code(500);
    exit('Connexion à la base impossible. Vérifiez config/config.php et importez database.sql.');
}

const ETATS = ['disponible' => 'Disponible', 'en_fonctionnement' => 'En fonctionnement', 'en_maintenance' => 'En maintenance', 'hors_service' => 'Hors service', 'ferme' => 'Fermé temporairement'];
const STATUTS_RES = ['en_attente' => 'En attente', 'confirmee' => 'Confirmée', 'payee' => 'Payée', 'utilisee' => 'Utilisée', 'annulee' => 'Annulée'];
const CAT_DEPENSES = ['maintenance' => 'Maintenance', 'pieces' => 'Pièces de rechange', 'electricite' => 'Électricité', 'salaires' => 'Salaires', 'securite' => 'Sécurité', 'nettoyage' => 'Nettoyage', 'assurance' => 'Assurance', 'marketing' => 'Marketing'];
const CAT_RECETTES = ['billets' => 'Billets', 'reservations' => 'Réservations', 'abonnements' => 'Abonnements', 'autres' => 'Autres'];
const ROLES = ['admin' => 'Administrateur', 'responsable' => 'Responsable de jeu', 'agent' => 'Agent d\'accueil', 'client' => 'Client'];

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $p = ''): string { return BASE_URL . '/' . ltrim($p, '/'); }
function redirect(string $p): never { header('Location: ' . (str_starts_with($p, 'http') ? $p : url($p))); exit; }
function money($v): string { return number_format((float)$v, 2, ',', ' ') . ' ' . DEVISE; }
function bookable(string $etat): bool { return in_array($etat, ['disponible', 'en_fonctionnement'], true); }
function flash(?string $msg = null, string $type = 'ok'): ?array {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(419); exit('Session expirée ou requête invalide. Rechargez la page.');
    }
}
function user(): ?array {
    global $pdo; static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $s = $pdo->prepare("SELECT * FROM utilisateurs WHERE id=? AND statut='actif'");
            $s->execute([$_SESSION['uid']]); $u = $s->fetch() ?: null;
            if (!$u) unset($_SESSION['uid']);
        }
    }
    return $u;
}
function game_img(array $g): ?string {
    // 1) image saisie dans la base (URL http ou chemin assets/...)
    if (!empty($g['image'])) {
        return preg_match('#^https?://#i', $g['image']) ? $g['image'] : url($g['image']);
    }
    // 2) sinon : photo déposée dans assets/img/jeux/ nommée d'après le jeu (ex. grande-roue.jpg) ou son id (2.jpg)
    $nom = strtr(mb_strtolower((string)($g['nom'] ?? ''), 'UTF-8'), [
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a', 'ä' => 'a',
        'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ö' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'œ' => 'oe',
    ]);
    $slug = trim((string)preg_replace('/[^a-z0-9]+/', '-', $nom), '-');
    foreach ([$slug, (string)($g['id'] ?? '')] as $base) {
        if ($base === '') continue;
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $rel = "assets/img/jeux/$base.$ext";
            if (is_file(__DIR__ . '/../' . $rel)) return url($rel);
        }
    }
    return null;
}
function check_password(string $input, string $stored): bool {
    // Mot de passe stocké en clair (visible dans phpMyAdmin) ; accepte aussi d'anciens hash bcrypt
    if (hash_equals($stored, $input)) return true;
    return str_starts_with($stored, '$2y$') && password_verify($input, $stored);
}
function home_for(string $role): string {
    return ['admin' => 'admin/dashboard.php', 'responsable' => 'responsable/dashboard.php', 'agent' => 'agent/validate.php'][$role] ?? 'index.php';
}
function require_login(string $msg = 'Connectez-vous pour continuer.'): array {
    $u = user();
    if (!$u) { flash($msg, 'warn'); $_SESSION['next'] = $_SERVER['REQUEST_URI']; redirect('login.php'); }
    return $u;
}
function require_role(string ...$roles): array {
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) { http_response_code(403); flash('Accès refusé pour votre rôle.', 'err'); redirect(home_for($u['role'])); }
    return $u;
}
function clean(string $k, int $max = 255): string { return mb_substr(trim((string)($_POST[$k] ?? '')), 0, $max); }
function post_int(string $k): int { return (int)($_POST[$k] ?? 0); }
function post_float(string $k): float { return (float)str_replace(',', '.', (string)($_POST[$k] ?? 0)); }
function valid_date(string $d): bool { $x = DateTime::createFromFormat('Y-m-d', $d); return $x && $x->format('Y-m-d') === $d; }
function badge(string $txt, string $cls): string { return '<span class="badge ' . e($cls) . '">' . e($txt) . '</span>'; }
function etat_badge(string $etat): string { return badge(ETATS[$etat] ?? $etat, 'etat-' . $etat); }
function statut_badge(string $s): string { return badge(STATUTS_RES[$s] ?? $s, 'st-' . $s); }
function unique_code(string $prefix, int $bytes = 4): string { return $prefix . strtoupper(bin2hex(random_bytes($bytes))); }

// Crée une commande + billets + recette dans une transaction (utilisé par client et agent)
function vendre_billets(PDO $pdo, int $jeu, int $type, int $qte, ?int $client, ?int $agent, string $date, string $mode): array {
    $pdo->beginTransaction();
    try {
        $j = $pdo->prepare('SELECT * FROM jeux WHERE id=? FOR UPDATE'); $j->execute([$jeu]); $j = $j->fetch();
        if (!$j || !bookable($j['etat'])) throw new RuntimeException('Ce jeu n\'est pas disponible à la vente.');
        $t = $pdo->prepare('SELECT * FROM type_billet WHERE id=? AND actif=1'); $t->execute([$type]); $t = $t->fetch();
        if (!$t) throw new RuntimeException('Type de billet invalide.');
        if ($qte < 1 || $qte > 20) throw new RuntimeException('Quantité invalide (1 à 20).');
        $total = round((float)$t['prix'] * $qte, 2);
        $num = unique_code('CMD-');
        $pdo->prepare('INSERT INTO commandes (numero,id_client,id_agent,id_jeu,id_type,quantite,montant_total,mode_paiement,statut_paiement) VALUES (?,?,?,?,?,?,?,?,\'paye\')')
            ->execute([$num, $client, $agent, $jeu, $type, $qte, $total, $mode]);
        $cid = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO billets (id_commande,id_type,id_jeu,code_unique,date_visite,prix_unitaire) VALUES (?,?,?,?,?,?)');
        for ($i = 0; $i < $qte; $i++) $ins->execute([$cid, $type, $jeu, unique_code('PJ-'), $date, $t['prix']]);
        $pdo->prepare('INSERT INTO recettes (categorie,description,montant,date_recette,id_jeu,id_commande,id_utilisateur) VALUES (\'billets\',?,?,CURDATE(),?,?,?)')
            ->execute(["Vente $num ({$t['libelle']} x$qte)", $total, $jeu, $cid, $agent ?? $client]);
        $pdo->commit();
        return ['id' => $cid, 'numero' => $num, 'total' => $total];
    } catch (Throwable $ex) { $pdo->rollBack(); throw $ex; }
}
