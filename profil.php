<?php
require __DIR__ . '/includes/bootstrap.php'; require __DIR__ . '/includes/layout.php';
$u = require_login(); $area = ['responsable' => 'responsable', 'agent' => 'agent'][$u['role']] ?? 'site';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'info') {
        if (clean('nom', 60) === '' || clean('prenom', 60) === '') flash('Nom et prénom obligatoires.', 'err');
        else { $pdo->prepare('UPDATE utilisateurs SET nom=?,prenom=?,telephone=? WHERE id=?')->execute([clean('nom', 60), clean('prenom', 60), clean('telephone', 25) ?: null, $u['id']]); flash('Profil mis à jour.'); }
    } else {
        $new = (string)($_POST['new'] ?? '');
        if (!check_password((string)($_POST['old'] ?? ''), (string)$u['mot_de_passe'])) flash('Mot de passe actuel incorrect.', 'err');
        elseif (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) flash('Nouveau mot de passe : 8 caractères, lettres et chiffres.', 'err');
        else { $pdo->prepare('UPDATE utilisateurs SET mot_de_passe=? WHERE id=?')->execute([$new, $u['id']]); flash('Mot de passe modifié.'); }
    }
    redirect('profil.php');
}
page_start('Mon profil', $area);
?>
<div class="card"><h3>Mes informations</h3><form method="post" class="formgrid"><?= csrf_field() ?><input type="hidden" name="action" value="info">
  <div><label>Nom</label><input name="nom" value="<?= e($u['nom']) ?>" required></div><div><label>Prénom</label><input name="prenom" value="<?= e($u['prenom']) ?>" required></div>
  <div><label>Email</label><input value="<?= e($u['email']) ?>" disabled></div><div><label>Téléphone</label><input name="telephone" value="<?= e($u['telephone']) ?>"></div>
  <div class="full"><button class="btn">Enregistrer</button></div></form></div>
<div class="card"><h3>Changer de mot de passe</h3><form method="post" class="formgrid"><?= csrf_field() ?><input type="hidden" name="action" value="pw">
  <div><label>Mot de passe actuel</label><input type="password" name="old" required></div><div><label>Nouveau mot de passe</label><input type="password" name="new" minlength="8" required></div>
  <div class="full"><button class="btn dark">Modifier</button></div></form></div>
<?php page_end($area);
