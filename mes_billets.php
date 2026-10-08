<?php
require __DIR__ . '/includes/bootstrap.php'; require __DIR__ . '/includes/layout.php';
$u = require_role('client');
$s = $pdo->prepare("SELECT c.*, j.nom jeu, j.emoji, t.libelle type, (SELECT MIN(date_visite) FROM billets WHERE id_commande=c.id) visite,
  (SELECT COUNT(*) FROM billets WHERE id_commande=c.id AND statut_utilisation='utilise') utilises FROM commandes c JOIN jeux j ON j.id=c.id_jeu JOIN type_billet t ON t.id=c.id_type WHERE c.id_client=? ORDER BY c.date_commande DESC");
$s->execute([$u['id']]); $rows = $s->fetchAll();
page_start('Mes billets');
?>
<?php if (!$rows): ?><div class="card">Aucun achat pour le moment. <a href="<?= url('jeux.php') ?>">Acheter un billet</a></div><?php else: ?>
<div class="tablewrap"><table><tr><th>Commande</th><th>Jeu</th><th>Type</th><th>Qté</th><th>Total</th><th>Visite</th><th>Utilisés</th><th>Paiement</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['numero']) ?></td><td><?= e($r['emoji'] . ' ' . $r['jeu']) ?></td><td><?= e($r['type']) ?></td><td><?= (int)$r['quantite'] ?></td><td><?= money($r['montant_total']) ?></td><td><?= e(date('d/m/Y', strtotime($r['visite']))) ?></td><td><?= (int)$r['utilises'] ?>/<?= (int)$r['quantite'] ?></td><td><?= badge($r['statut_paiement'] === 'paye' ? 'Payé' : ucfirst($r['statut_paiement']), $r['statut_paiement'] === 'paye' ? 'st-payee' : 'st-en_attente') ?></td>
<td><a class="btn sm alt" href="<?= url('ticket.php?type=cmd&id=' . (int)$r['id']) ?>">Voir / imprimer</a></td></tr><?php endforeach; ?></table></div><?php endif; ?>
<?php page_end();
