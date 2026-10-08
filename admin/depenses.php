<?php
require __DIR__ . '/../includes/bootstrap.php'; require __DIR__ . '/../includes/layout.php';
$u = require_role('admin');
function upload_justificatif(): ?string {
    if (empty($_FILES['justificatif']['name']) || $_FILES['justificatif']['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES['justificatif']; if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 2 * 1024 * 1024) throw new RuntimeException('Justificatif : fichier de 2 Mo maximum.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']); $ext = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'][$mime] ?? null;
    if (!$ext) throw new RuntimeException('Justificatif : PDF, JPG ou PNG uniquement.');
    $name = bin2hex(random_bytes(12)) . '.' . $ext; move_uploaded_file($f['tmp_name'], __DIR__ . '/../uploads/' . $name); return $name;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $act = $_POST['action'] ?? ''; $id = post_int('id');
    if ($act === 'delete') { $pdo->prepare('DELETE FROM depenses WHERE id=?')->execute([$id]); flash('Dépense supprimée.'); redirect('admin/depenses.php'); }
    $d = ['lib' => clean('libelle', 120), 'cat' => $_POST['categorie'] ?? '', 'desc' => clean('description'), 'm' => post_float('montant'), 'date' => clean('date_depense', 10), 'jeu' => post_int('id_jeu')];
    $err = []; if ($d['lib'] === '') $err[] = 'Libellé obligatoire.'; if (!isset(CAT_DEPENSES[$d['cat']])) $err[] = 'Catégorie invalide.'; if ($d['m'] <= 0) $err[] = 'Montant > 0 requis.'; if (!valid_date($d['date'])) $err[] = 'Date invalide.';
    try { $just = $err ? null : upload_justificatif(); } catch (RuntimeException $ex) { $err[] = $ex->getMessage(); $just = null; }
    if ($err) { flash(implode(' ', $err), 'err'); redirect('admin/depenses.php?' . ($id ? "edit=$id" : 'add=1')); }
    $type = $d['jeu'] ? 'jeu' : 'general';
    if ($id) { $pdo->prepare('UPDATE depenses SET libelle=?,categorie=?,description=?,montant=?,date_depense=?,type=?,id_jeu=?' . ($just ? ',justificatif=?' : '') . ' WHERE id=?')->execute(array_merge([$d['lib'], $d['cat'], $d['desc'] ?: null, $d['m'], $d['date'], $type, $d['jeu'] ?: null], $just ? [$just] : [], [$id])); }
    else $pdo->prepare('INSERT INTO depenses (libelle,categorie,description,montant,date_depense,type,id_jeu,id_utilisateur,justificatif) VALUES (?,?,?,?,?,?,?,?,?)')->execute([$d['lib'], $d['cat'], $d['desc'] ?: null, $d['m'], $d['date'], $type, $d['jeu'] ?: null, $u['id'], $just]);
    flash('Dépense enregistrée.'); redirect('admin/depenses.php');
}
$jeux = $pdo->query('SELECT id,nom FROM jeux ORDER BY nom')->fetchAll();
page_start('Dépenses', 'admin');
$edit = null; if (isset($_GET['edit'])) { $s = $pdo->prepare('SELECT * FROM depenses WHERE id=?'); $s->execute([(int)$_GET['edit']]); $edit = $s->fetch() ?: null; }
if ($edit || isset($_GET['add'])): $x = $edit ?: ['id' => 0, 'libelle' => '', 'categorie' => 'maintenance', 'description' => '', 'montant' => '', 'date_depense' => date('Y-m-d'), 'id_jeu' => 0, 'justificatif' => null]; ?>
<div class="card"><h3><?= $edit ? 'Modifier' : 'Ajouter' ?> une dépense</h3><form method="post" enctype="multipart/form-data" class="formgrid"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
<div><label>Libellé</label><input name="libelle" value="<?= e($x['libelle']) ?>" required></div><div><label>Catégorie</label><select name="categorie"><?php foreach (CAT_DEPENSES as $k => $v): ?><option value="<?= e($k) ?>" <?= $k === $x['categorie'] ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
<div><label>Montant (<?= e(DEVISE) ?>)</label><input type="number" step="0.01" min="0.01" name="montant" value="<?= e($x['montant']) ?>" required></div><div><label>Date</label><input type="date" name="date_depense" value="<?= e($x['date_depense']) ?>" required></div>
<div><label>Jeu concerné (vide = frais généraux)</label><select name="id_jeu"><option value="0">Frais généraux</option><?php foreach ($jeux as $j): ?><option value="<?= (int)$j['id'] ?>" <?= (int)$j['id'] === (int)$x['id_jeu'] ? 'selected' : '' ?>><?= e($j['nom']) ?></option><?php endforeach; ?></select></div>
<div><label>Justificatif (PDF/JPG/PNG, 2 Mo)</label><input type="file" name="justificatif" accept=".pdf,.jpg,.jpeg,.png"></div>
<div class="full"><label>Description</label><input name="description" value="<?= e($x['description']) ?>" maxlength="255"></div><div class="full"><button class="btn">Enregistrer</button> <a class="btn alt" href="<?= url('admin/depenses.php') ?>">Annuler</a></div></form></div>
<?php else:
    $d1 = $_GET['d1'] ?? ''; $d2 = $_GET['d2'] ?? ''; $cat = $_GET['cat'] ?? ''; $fj = (int)($_GET['jeu'] ?? 0); $q = trim($_GET['q'] ?? ''); $p = []; $w = ' WHERE 1';
    if (valid_date($d1)) { $w .= ' AND date_depense>=?'; $p[] = $d1; } if (valid_date($d2)) { $w .= ' AND date_depense<=?'; $p[] = $d2; } if (isset(CAT_DEPENSES[$cat])) { $w .= ' AND d.categorie=?'; $p[] = $cat; }
    if ($fj) { $w .= ' AND d.id_jeu=?'; $p[] = $fj; } if ($q !== '') { $w .= ' AND (libelle LIKE ? OR d.description LIKE ?)'; array_push($p, "%$q%", "%$q%"); }
    $s = $pdo->prepare("SELECT d.*, j.nom jeu FROM depenses d LEFT JOIN jeux j ON j.id=d.id_jeu $w ORDER BY date_depense DESC, d.id DESC"); $s->execute($p); $rows = $s->fetchAll(); ?>
<form class="filters"><div><label>Du</label><input type="date" name="d1" value="<?= e($d1) ?>"></div><div><label>Au</label><input type="date" name="d2" value="<?= e($d2) ?>"></div>
<div><label>Catégorie</label><select name="cat"><option value="">Toutes</option><?php foreach (CAT_DEPENSES as $k => $v): ?><option value="<?= e($k) ?>" <?= $k === $cat ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
<div><label>Jeu</label><select name="jeu"><option value="0">Tous</option><?php foreach ($jeux as $j): ?><option value="<?= (int)$j['id'] ?>" <?= $fj === (int)$j['id'] ? 'selected' : '' ?>><?= e($j['nom']) ?></option><?php endforeach; ?></select></div>
<div><label>Recherche</label><input name="q" value="<?= e($q) ?>"></div><button class="btn">Filtrer</button> <a class="btn dark" href="?add=1">+ Ajouter</a></form>
<p class="total">Total affiché : <?= money(array_sum(array_column($rows, 'montant'))) ?></p>
<div class="tablewrap"><table><tr><th>Date</th><th>Libellé</th><th>Catégorie</th><th>Type</th><th>Jeu</th><th class="right">Montant</th><th>Justif.</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e(date('d/m/Y', strtotime($r['date_depense']))) ?></td><td><?= e($r['libelle']) ?></td><td><?= e(CAT_DEPENSES[$r['categorie']] ?? $r['categorie']) ?></td><td><?= $r['type'] === 'jeu' ? 'Lié à un jeu' : 'Frais généraux' ?></td><td><?= e($r['jeu'] ?: '—') ?></td><td class="right"><?= money($r['montant']) ?></td>
<td><?= $r['justificatif'] ? '<a href="' . e(url('uploads/' . $r['justificatif'])) . '" target="_blank" rel="noopener">Voir</a>' : '—' ?></td>
<td><a class="btn sm alt" href="?edit=<?= (int)$r['id'] ?>">Modifier</a> <form method="post" data-confirm="Supprimer cette dépense ?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn sm red">Supprimer</button></form></td></tr><?php endforeach; ?></table></div>
<?php endif; page_end('admin');
