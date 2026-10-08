<?php
require __DIR__ . '/../includes/bootstrap.php'; require __DIR__ . '/../includes/layout.php';
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); $pdo->prepare("UPDATE billets SET statut_utilisation='annule' WHERE id=? AND statut_utilisation='valide'")->execute([post_int('id')]); flash('Billet annulé.'); redirect('admin/billets.php'); }
$q = trim($_GET['q'] ?? ''); $jeu = (int)($_GET['jeu'] ?? 0); $st = $_GET['st'] ?? ''; $d1 = $_GET['d1'] ?? ''; $p = [];
$sql = "SELECT b.*, j.nom jeu, t.libelle type, c.numero cmd, CONCAT(u.prenom,' ',u.nom) client FROM billets b JOIN jeux j ON j.id=b.id_jeu JOIN type_billet t ON t.id=b.id_type JOIN commandes c ON c.id=b.id_commande LEFT JOIN utilisateurs u ON u.id=c.id_client WHERE 1";
if ($q !== '') { $sql .= ' AND (b.code_unique LIKE ? OR c.numero LIKE ? OR u.nom LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%"); }
if ($jeu) { $sql .= ' AND b.id_jeu=?'; $p[] = $jeu; } if (in_array($st, ['valide', 'utilise', 'annule'], true)) { $sql .= ' AND b.statut_utilisation=?'; $p[] = $st; } if (valid_date($d1)) { $sql .= ' AND b.date_visite>=?'; $p[] = $d1; }
$s = $pdo->prepare($sql . ' ORDER BY b.id DESC LIMIT 300'); $s->execute($p); $rows = $s->fetchAll(); $jeux = $pdo->query('SELECT id,nom FROM jeux ORDER BY nom')->fetchAll();
page_start('Billets', 'admin');
?>
<form class="filters"><div><label>Code / commande / client</label><input name="q" value="<?= e($q) ?>"></div><div><label>Jeu</label><select name="jeu"><option value="0">Tous</option><?php foreach ($jeux as $j): ?><option value="<?= (int)$j['id'] ?>" <?= $jeu === (int)$j['id'] ? 'selected' : '' ?>><?= e($j['nom']) ?></option><?php endforeach; ?></select></div>
<div><label>Statut</label><select name="st"><option value="">Tous</option><?php foreach (['valide' => 'Valide', 'utilise' => 'Utilisé', 'annule' => 'Annulé'] as $k => $v): ?><option value="<?= $k ?>" <?= $k === $st ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div><div><label>Visite depuis</label><input type="date" name="d1" value="<?= e($d1) ?>"></div><button class="btn">Filtrer</button></form>
<div class="tablewrap"><table><tr><th>Code</th><th>Commande</th><th>Client</th><th>Jeu</th><th>Type</th><th>Visite</th><th class="right">Prix</th><th>Statut</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><b><?= e($r['code_unique']) ?></b></td><td><?= e($r['cmd']) ?></td><td><?= e($r['client'] ?: 'Vente sur place') ?></td><td><?= e($r['jeu']) ?></td><td><?= e($r['type']) ?></td><td><?= e(date('d/m/Y', strtotime($r['date_visite']))) ?></td><td class="right"><?= money($r['prix_unitaire']) ?></td>
<td><?= badge($r['statut_utilisation'], $r['statut_utilisation'] === 'valide' ? 'st-confirmee' : ($r['statut_utilisation'] === 'utilise' ? 'st-payee' : 'st-annulee')) ?></td>
<td><?php if ($r['statut_utilisation'] === 'valide'): ?><form method="post" data-confirm="Annuler ce billet ?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn sm red">Annuler</button></form><?php endif; ?></td></tr><?php endforeach; ?></table></div>
<?php page_end('admin');
