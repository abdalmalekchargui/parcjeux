<?php
require __DIR__ . '/../includes/bootstrap.php'; require __DIR__ . '/../includes/layout.php';
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $id = post_int('id'); $new = $_POST['statut'] ?? '';
    $s = $pdo->prepare('SELECT * FROM reservations WHERE id=?'); $s->execute([$id]); $r = $s->fetch();
    if ($r && isset(STATUTS_RES[$new])) {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE reservations SET statut=? WHERE id=?')->execute([$new, $id]);
        $has = $pdo->prepare('SELECT COUNT(*) FROM recettes WHERE id_reservation=?'); $has->execute([$id]); $has = (int)$has->fetchColumn();
        if (in_array($new, ['payee', 'utilisee'], true) && !$has) $pdo->prepare("INSERT INTO recettes (categorie,description,montant,date_recette,id_jeu,id_reservation) VALUES ('reservations',?,?,CURDATE(),?,?)")->execute(['Réservation ' . $r['numero'], $r['montant_total'], $r['id_jeu'], $id]);
        if ($new === 'annulee') $pdo->prepare('DELETE FROM recettes WHERE id_reservation=?')->execute([$id]);
        $pdo->commit(); flash('Statut mis à jour.');
    }
    redirect('admin/reservations.php');
}
$f = ['d1' => $_GET['d1'] ?? '', 'd2' => $_GET['d2'] ?? '', 'jeu' => (int)($_GET['jeu'] ?? 0), 'st' => $_GET['st'] ?? '', 'q' => trim($_GET['q'] ?? '')];
$sql = "SELECT r.*, CONCAT(u.prenom,' ',u.nom) client, j.nom jeu FROM reservations r JOIN utilisateurs u ON u.id=r.id_client JOIN jeux j ON j.id=r.id_jeu WHERE 1"; $p = [];
if (valid_date($f['d1'])) { $sql .= ' AND r.date_visite>=?'; $p[] = $f['d1']; } if (valid_date($f['d2'])) { $sql .= ' AND r.date_visite<=?'; $p[] = $f['d2']; }
if ($f['jeu']) { $sql .= ' AND r.id_jeu=?'; $p[] = $f['jeu']; } if (isset(STATUTS_RES[$f['st']])) { $sql .= ' AND r.statut=?'; $p[] = $f['st']; }
if ($f['q'] !== '') { $sql .= ' AND (u.nom LIKE ? OR u.prenom LIKE ? OR r.numero LIKE ?)'; array_push($p, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%"); }
$st = $pdo->prepare($sql . ' ORDER BY r.date_visite DESC, r.heure DESC'); $st->execute($p); $rows = $st->fetchAll();
$jeux = $pdo->query('SELECT id,nom FROM jeux ORDER BY nom')->fetchAll();
page_start('Réservations', 'admin');
?>
<form class="filters"><div><label>Du</label><input type="date" name="d1" value="<?= e($f['d1']) ?>"></div><div><label>Au</label><input type="date" name="d2" value="<?= e($f['d2']) ?>"></div>
<div><label>Jeu</label><select name="jeu"><option value="0">Tous</option><?php foreach ($jeux as $j): ?><option value="<?= (int)$j['id'] ?>" <?= $f['jeu'] === (int)$j['id'] ? 'selected' : '' ?>><?= e($j['nom']) ?></option><?php endforeach; ?></select></div>
<div><label>Statut</label><select name="st"><option value="">Tous</option><?php foreach (STATUTS_RES as $k => $v): ?><option value="<?= e($k) ?>" <?= $k === $f['st'] ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
<div><label>Client / n°</label><input name="q" value="<?= e($f['q']) ?>"></div><button class="btn">Filtrer</button></form>
<div class="tablewrap"><table><tr><th>N°</th><th>Client</th><th>Jeu</th><th>Visite</th><th>Pers.</th><th class="right">Montant</th><th>Statut</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['numero']) ?></td><td><?= e($r['client']) ?></td><td><?= e($r['jeu']) ?></td><td><?= e(date('d/m/Y', strtotime($r['date_visite'])) . ' ' . substr($r['heure'], 0, 5)) ?></td><td><?= (int)$r['nb_personnes'] ?></td><td class="right"><?= money($r['montant_total']) ?></td>
<td><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><select name="statut" onchange="this.form.submit()" style="width:auto"><?php foreach (STATUTS_RES as $k => $v): ?><option value="<?= e($k) ?>" <?= $k === $r['statut'] ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></form></td>
<td><a class="btn sm alt" href="<?= url('ticket.php?type=res&id=' . (int)$r['id']) ?>">Ticket</a></td></tr><?php endforeach; ?></table></div>
<?php if (!$rows) echo '<p class="muted">Aucune réservation.</p>'; page_end('admin');
