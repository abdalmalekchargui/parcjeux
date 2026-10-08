<?php
require __DIR__ . '/../includes/bootstrap.php'; require __DIR__ . '/../includes/layout.php';
$u = require_role('agent', 'admin'); $b = null; $code = strtoupper(trim($_GET['code'] ?? $_POST['code'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'use') {
    csrf_check();
    // UPDATE conditionnel : impossible d'utiliser deux fois le même billet
    $n = $pdo->prepare("UPDATE billets SET statut_utilisation='utilise', date_utilisation=NOW() WHERE code_unique=? AND statut_utilisation='valide' AND date_visite<=CURDATE()"); $n->execute([$code]);
    flash($n->rowCount() ? 'Entrée enregistrée : billet utilisé.' : 'Impossible : billet déjà utilisé, annulé ou pas encore valable.', $n->rowCount() ? 'ok' : 'err');
    redirect('agent/validate.php?code=' . urlencode($code));
}
if ($code !== '') { $s = $pdo->prepare('SELECT b.*, j.nom jeu, j.etat, t.libelle type FROM billets b JOIN jeux j ON j.id=b.id_jeu JOIN type_billet t ON t.id=b.id_type WHERE b.code_unique=?'); $s->execute([$code]); $b = $s->fetch(); }
page_start('Valider un billet', $u['role'] === 'admin' ? 'admin' : 'agent');
?>
<form class="filters"><div><label>Code du billet</label><input name="code" value="<?= e($code) ?>" placeholder="PJ-XXXXXXXX" autofocus></div><button class="btn">Vérifier</button></form>
<?php if ($code !== '' && !$b): ?><div class="alert err">Aucun billet avec ce code.</div><?php endif;
if ($b): $ok = $b['statut_utilisation'] === 'valide' && $b['date_visite'] <= date('Y-m-d'); ?>
<div class="card" style="border-color:<?= $ok ? '#12b5a6' : '#e63946' ?>"><h3><?= $ok ? '✔ Billet valide' : '✖ Billet non valable' ?></h3>
<p><b><?= e($b['code_unique']) ?></b> · <?= e($b['type']) ?> · <?= e($b['jeu']) ?> · visite le <?= e(date('d/m/Y', strtotime($b['date_visite']))) ?></p>
<p>Statut : <b><?= e($b['statut_utilisation']) ?></b><?= $b['date_utilisation'] ? ' (le ' . e(date('d/m/Y H:i', strtotime($b['date_utilisation']))) . ')' : '' ?></p>
<?php if ($b['statut_utilisation'] === 'valide' && $b['date_visite'] > date('Y-m-d')): ?><p class="muted">Ce billet n'est valable qu'à partir de sa date de visite.</p><?php endif;
if ($ok): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="use"><input type="hidden" name="code" value="<?= e($b['code_unique']) ?>"><button class="btn">Enregistrer l'entrée</button></form><?php endif; ?></div><?php endif;
page_end($u['role'] === 'admin' ? 'admin' : 'agent');
