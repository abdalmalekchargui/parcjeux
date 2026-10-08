<?php
// Ticket / confirmation imprimable : bouton "Imprimer" -> "Enregistrer au format PDF"
require __DIR__ . '/includes/bootstrap.php'; require __DIR__ . '/includes/layout.php';
$u = require_login(); $id = (int)($_GET['id'] ?? 0); $type = $_GET['type'] ?? '';
$staff = in_array($u['role'], ['admin', 'agent'], true);
if ($type === 'res') {
    $s = $pdo->prepare('SELECT r.*, j.nom jeu, j.emoji, c.nom cnom, c.prenom cprenom, c.email cemail FROM reservations r JOIN jeux j ON j.id=r.id_jeu JOIN utilisateurs c ON c.id=r.id_client WHERE r.id=?');
} else {
    $s = $pdo->prepare('SELECT r.*, j.nom jeu, j.emoji, c.nom cnom, c.prenom cprenom, c.email cemail, t.libelle FROM commandes r JOIN jeux j ON j.id=r.id_jeu JOIN type_billet t ON t.id=r.id_type LEFT JOIN utilisateurs c ON c.id=r.id_client WHERE r.id=?');
}
$s->execute([$id]); $r = $s->fetch();
if (!$r || (!$staff && (int)$r['id_client'] !== (int)$u['id'])) { http_response_code(404); flash('Document introuvable.', 'err'); redirect('index.php'); }
page_start($type === 'res' ? 'Réservation ' . $r['numero'] : 'Commande ' . $r['numero'], $staff ? ($u['role'] === 'admin' ? 'admin' : 'agent') : 'site');
?>
<p class="noprint"><button class="btn" onclick="window.print()">Imprimer / Enregistrer en PDF</button></p>
<div class="ticket"><h2>🎪 <?= e(PARC_NOM) ?></h2>
<?php if ($type === 'res'): ?>
  <p class="code"><?= e($r['numero']) ?></p><p><?= statut_badge($r['statut']) ?></p>
  <p><b>Client :</b> <?= e($r['cprenom'] . ' ' . $r['cnom']) ?> · <?= e($r['cemail']) ?> · <?= e($r['telephone'] ?: '—') ?></p>
  <p><b>Jeu réservé :</b> <?= e($r['emoji'] . ' ' . $r['jeu']) ?></p>
  <p><b>Date :</b> <?= e(date('d/m/Y', strtotime($r['date_visite']))) ?> · <b>Heure :</b> <?= e(substr($r['heure'], 0, 5)) ?> · <b>Personnes :</b> <?= (int)$r['nb_personnes'] ?></p>
  <p><b>Prix total :</b> <?= money($r['montant_total']) ?></p><p class="muted">Créée le <?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></p>
<?php else:
  $b = $pdo->prepare('SELECT * FROM billets WHERE id_commande=?'); $b->execute([$id]); $billets = $b->fetchAll(); ?>
  <p class="code"><?= e($r['numero']) ?></p><p><b>Client :</b> <?= e($r['cnom'] ? $r['cprenom'] . ' ' . $r['cnom'] : 'Vente sur place') ?> · <b>Jeu :</b> <?= e($r['emoji'] . ' ' . $r['jeu']) ?></p>
  <p><b>Billets :</b> <?= e($r['libelle']) ?> × <?= (int)$r['quantite'] ?> · <b>Total :</b> <?= money($r['montant_total']) ?> · <?= e($r['mode_paiement']) ?></p>
  <?php foreach ($billets as $bl): ?><p style="border-top:2px dashed #1d1a3a;padding-top:8px"><span class="code"><?= e($bl['code_unique']) ?></span><br><small>Valable le <?= e(date('d/m/Y', strtotime($bl['date_visite']))) ?> · <?= e($bl['statut_utilisation']) ?></small></p><?php endforeach; ?>
<?php endif; ?></div>
<?php page_end($staff ? ($u['role'] === 'admin' ? 'admin' : 'agent') : 'site');
