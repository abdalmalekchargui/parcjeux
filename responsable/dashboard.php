<?php
require __DIR__ . '/../includes/bootstrap.php'; require __DIR__ . '/../includes/layout.php';
$u = require_role('responsable');
$s = $pdo->prepare('SELECT * FROM jeux WHERE id_responsable=? ORDER BY nom'); $s->execute([$u['id']]); $jeux = $s->fetchAll();
$c = $pdo->prepare("SELECT COUNT(*) FROM reservations r JOIN jeux j ON j.id=r.id_jeu WHERE j.id_responsable=? AND r.date_visite>=CURDATE() AND r.statut NOT IN ('annulee','utilisee')"); $c->execute([$u['id']]); $res = (int)$c->fetchColumn();
$m = $pdo->prepare("SELECT COUNT(*) FROM maintenance m JOIN jeux j ON j.id=m.id_jeu WHERE j.id_responsable=? AND m.statut<>'terminee'"); $m->execute([$u['id']]); $mt = (int)$m->fetchColumn();
$rev = $pdo->prepare('SELECT j.nom, m.date_prochaine_revision d FROM maintenance m JOIN jeux j ON j.id=m.id_jeu WHERE j.id_responsable=? AND m.date_prochaine_revision>=CURDATE() ORDER BY d LIMIT 5'); $rev->execute([$u['id']]); $rev = $rev->fetchAll();
page_start('Tableau de bord', 'responsable');
?>
<div class="stats"><div class="stat"><small>Mes jeux</small><b data-count="<?= count($jeux) ?>">0</b></div><div class="stat" style="--c:#6c4bd1"><small>Réservations à venir</small><b data-count="<?= $res ?>">0</b></div><div class="stat" style="--c:#f4a100"><small>Interventions ouvertes</small><b data-count="<?= $mt ?>">0</b></div></div>
<?php if ($rev): ?><div class="alert warn">Révisions prévues : <?php foreach ($rev as $r) echo e($r['nom'] . ' (' . date('d/m', strtotime($r['d'])) . ') '); ?></div><?php endif; ?>
<h3>État de mes jeux</h3><?php if (!$jeux): ?><p class="muted">Aucun jeu ne vous est affecté pour le moment.</p><?php endif; ?>
<div class="grid"><?php foreach ($jeux as $g): ?><div class="card" style="border-left:10px solid <?= e($g['couleur']) ?>"><h3><?= e($g['emoji'] . ' ' . $g['nom']) ?></h3><p><?= etat_badge($g['etat']) ?></p>
<form method="post" action="<?= url('responsable/jeux.php') ?>"><?= csrf_field() ?><input type="hidden" name="action" value="etat"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>"><label>Changer l'état</label><select name="etat" onchange="this.form.submit()"><?php foreach (ETATS as $k => $v): ?><option value="<?= e($k) ?>" <?= $k === $g['etat'] ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></form></div><?php endforeach; ?></div>
<?php page_end('responsable');
