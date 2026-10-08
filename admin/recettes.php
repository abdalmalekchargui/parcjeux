<?php
require __DIR__ . '/../includes/bootstrap.php'; require __DIR__ . '/../includes/layout.php';
$u = require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $act = $_POST['action'] ?? '';
    if ($act === 'delete') { $pdo->prepare('DELETE FROM recettes WHERE id=? AND id_commande IS NULL AND id_reservation IS NULL')->execute([post_int('id')]); flash('Recette manuelle supprimée (les recettes automatiques sont protégées).'); }
    else {
        $cat = $_POST['categorie'] ?? ''; $m = post_float('montant'); $dt = clean('date_recette', 10);
        if (!isset(CAT_RECETTES[$cat]) || clean('description') === '' || $m <= 0 || !valid_date($dt)) flash('Catégorie, description, montant > 0 et date valides requis.', 'err');
        else { $pdo->prepare('INSERT INTO recettes (categorie,description,montant,date_recette,id_jeu,id_utilisateur) VALUES (?,?,?,?,?,?)')->execute([$cat, clean('description'), $m, $dt, post_int('id_jeu') ?: null, $u['id']]); flash('Recette ajoutée.'); }
    }
    redirect('admin/recettes.php');
}
$d1 = $_GET['d1'] ?? ''; $d2 = $_GET['d2'] ?? ''; $cat = $_GET['cat'] ?? ''; $q = trim($_GET['q'] ?? ''); $p = []; $w = ' WHERE 1';
if (valid_date($d1)) { $w .= ' AND date_recette>=?'; $p[] = $d1; } if (valid_date($d2)) { $w .= ' AND date_recette<=?'; $p[] = $d2; }
if (isset(CAT_RECETTES[$cat])) { $w .= ' AND categorie=?'; $p[] = $cat; } if ($q !== '') { $w .= ' AND description LIKE ?'; $p[] = "%$q%"; }
$s = $pdo->prepare("SELECT r.*, j.nom jeu FROM recettes r LEFT JOIN jeux j ON j.id=r.id_jeu $w ORDER BY date_recette DESC, id DESC"); $s->execute($p); $rows = $s->fetchAll();
$tot = array_sum(array_column($rows, 'montant')); $jeux = $pdo->query('SELECT id,nom FROM jeux ORDER BY nom')->fetchAll();
page_start('Recettes', 'admin');
?>
<form class="filters"><div><label>Du</label><input type="date" name="d1" value="<?= e($d1) ?>"></div><div><label>Au</label><input type="date" name="d2" value="<?= e($d2) ?>"></div>
<div><label>Type</label><select name="cat"><option value="">Tous</option><?php foreach (CAT_RECETTES as $k => $v): ?><option value="<?= e($k) ?>" <?= $k === $cat ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div><div><label>Recherche</label><input name="q" value="<?= e($q) ?>"></div><button class="btn">Filtrer</button></form>
<p class="total">Total affiché : <?= money($tot) ?></p>
<div class="tablewrap"><table><tr><th>Date</th><th>Type</th><th>Description</th><th>Jeu</th><th class="right">Montant</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e(date('d/m/Y', strtotime($r['date_recette']))) ?></td><td><?= e(CAT_RECETTES[$r['categorie']]) ?></td><td><?= e($r['description']) ?></td><td><?= e($r['jeu'] ?: '—') ?></td><td class="right"><?= money($r['montant']) ?></td>
<td><?php if (!$r['id_commande'] && !$r['id_reservation']): ?><form method="post" data-confirm="Supprimer cette recette ?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn sm red">Supprimer</button></form><?php else: ?><small class="muted">auto</small><?php endif; ?></td></tr><?php endforeach; ?></table></div>
<div class="card" style="margin-top:20px"><h3>Ajouter une recette (autres sources)</h3><form method="post" class="formgrid"><?= csrf_field() ?>
<div><label>Type</label><select name="categorie"><option value="autres">Autres</option><option value="abonnements">Abonnements</option></select></div><div><label>Montant</label><input type="number" step="0.01" min="0.01" name="montant" required></div>
<div><label>Date</label><input type="date" name="date_recette" value="<?= date('Y-m-d') ?>" required></div><div><label>Jeu (facultatif)</label><select name="id_jeu"><option value="">—</option><?php foreach ($jeux as $j): ?><option value="<?= (int)$j['id'] ?>"><?= e($j['nom']) ?></option><?php endforeach; ?></select></div>
<div class="full"><label>Description</label><input name="description" maxlength="255" required></div><div class="full"><button class="btn">Ajouter</button></div></form></div>
<?php page_end('admin');
