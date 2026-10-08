<?php
require __DIR__ . '/includes/bootstrap.php'; require __DIR__ . '/includes/layout.php';
$u = require_role('client');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $id = post_int('id'); $act = $_POST['action'] ?? '';
    $s = $pdo->prepare('SELECT * FROM reservations WHERE id=? AND id_client=?'); $s->execute([$id, $u['id']]); $r = $s->fetch();
    if (!$r) flash('Réservation introuvable.', 'err');
    elseif ($act === 'pay' && in_array($r['statut'], ['en_attente', 'confirmee'], true)) {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE reservations SET statut='payee' WHERE id=?")->execute([$id]);
        $pdo->prepare("INSERT INTO recettes (categorie,description,montant,date_recette,id_jeu,id_reservation,id_utilisateur) VALUES ('reservations',?,?,CURDATE(),?,?,?)")
            ->execute(['Réservation ' . $r['numero'], $r['montant_total'], $r['id_jeu'], $id, $u['id']]);
        $pdo->commit(); flash('Paiement simulé accepté.');
    } elseif ($act === 'cancel' && in_array($r['statut'], ['en_attente', 'confirmee', 'payee'], true) && $r['date_visite'] > date('Y-m-d')) {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE reservations SET statut='annulee' WHERE id=?")->execute([$id]);
        $pdo->prepare('DELETE FROM recettes WHERE id_reservation=?')->execute([$id]); // remboursement
        $pdo->commit(); flash('Réservation annulée.');
    } else flash('Action impossible : l\'annulation est possible jusqu\'à la veille de la visite.', 'err');
    redirect('mes_reservations.php');
}
$s = $pdo->prepare('SELECT r.*, j.nom jeu, j.emoji FROM reservations r JOIN jeux j ON j.id=r.id_jeu WHERE id_client=? ORDER BY r.created_at DESC'); $s->execute([$u['id']]); $rows = $s->fetchAll();
page_start('Mes réservations');
?>
<?php if (!$rows): ?><div class="card">Aucune réservation pour le moment. <a href="<?= url('jeux.php') ?>">Choisir un jeu</a></div><?php else: ?>
<div class="tablewrap"><table><tr><th>N°</th><th>Jeu</th><th>Visite</th><th>Pers.</th><th>Montant</th><th>Statut</th><th>Actions</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['numero']) ?></td><td><?= e($r['emoji'] . ' ' . $r['jeu']) ?></td><td><?= e(date('d/m/Y', strtotime($r['date_visite'])) . ' ' . substr($r['heure'], 0, 5)) ?></td><td><?= (int)$r['nb_personnes'] ?></td><td><?= money($r['montant_total']) ?></td><td><?= statut_badge($r['statut']) ?></td>
<td><?php if ($r['statut'] !== 'annulee'): ?><a class="btn sm alt" href="<?= url('ticket.php?type=res&id=' . (int)$r['id']) ?>">Ticket PDF</a> <?php endif;
 if (in_array($r['statut'], ['en_attente', 'confirmee'], true)): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="pay"><button class="btn sm">Payer (simulation)</button></form> <?php endif;
 if (in_array($r['statut'], ['en_attente', 'confirmee', 'payee'], true) && $r['date_visite'] > date('Y-m-d')): ?><form method="post" data-confirm="Annuler cette réservation ?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="cancel"><button class="btn sm red">Annuler</button></form><?php endif; ?></td></tr><?php endforeach; ?></table></div>
<p class="muted">Règle d'annulation : possible jusqu'à la veille de la visite ; une réservation payée est alors remboursée.</p><?php endif; ?>
<?php page_end();
