<?php
require __DIR__ . '/includes/bootstrap.php'; require __DIR__ . '/includes/layout.php'; require __DIR__ . '/includes/gate.php';
$u = client_gate('effectuer une réservation'); $g = load_jeu($pdo);
$creneaux = []; for ($h = 10; $h <= 20; $h++) $creneaux[] = sprintf('%02d:00', $h);
$d = ['date' => date('Y-m-d', strtotime('+1 day')), 'heure' => '10:00', 'nb' => 2, 'tel' => $u['telephone'] ?? '', 'com' => ''];
$step = 'form';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $d = ['date' => clean('date_visite', 10), 'heure' => clean('heure', 5), 'nb' => post_int('nb_personnes'), 'tel' => clean('telephone', 25), 'com' => clean('commentaire', 255)];
    $err = [];
    if (!valid_date($d['date']) || $d['date'] < date('Y-m-d')) $err[] = 'Choisissez une date valide (aujourd\'hui ou plus tard).';
    elseif ($d['date'] === date('Y-m-d') && $d['heure'] <= date('H:i')) $err[] = 'Ce créneau est déjà passé.';
    if (!in_array($d['heure'], $creneaux, true)) $err[] = 'Créneau invalide.';
    if ($d['nb'] < 1 || $d['nb'] > (int)$g['capacite']) $err[] = 'Nombre de personnes : entre 1 et ' . (int)$g['capacite'] . '.';
    if ($d['tel'] !== '' && !preg_match('/^[0-9 +().-]{8,20}$/', $d['tel'])) $err[] = 'Téléphone invalide.';
    if ($err) foreach ($err as $x) flash(implode(' ', $err), 'err');
    elseif (($_POST['action'] ?? '') === 'confirm') {
        $pdo->beginTransaction();
        try {
            // Verrou sur le jeu : évite les doubles réservations concurrentes
            $pdo->prepare('SELECT id FROM jeux WHERE id=? FOR UPDATE')->execute([$g['id']]);
            $r = $pdo->prepare("SELECT COALESCE(SUM(nb_personnes),0) FROM reservations WHERE id_jeu=? AND date_visite=? AND heure=? AND statut<>'annulee'");
            $r->execute([$g['id'], $d['date'], $d['heure'] . ':00']); $pris = (int)$r->fetchColumn();
            $reste = (int)$g['capacite'] - $pris;
            if ($d['nb'] > $reste) throw new RuntimeException($reste > 0 ? "Il ne reste que $reste place(s) sur ce créneau." : 'Ce créneau est complet.');
            $total = round((float)$g['tarif'] * $d['nb'], 2);
            $pdo->prepare("INSERT INTO reservations (numero,id_client,id_jeu,date_visite,heure,nb_personnes,montant_total,statut,telephone,commentaire) VALUES (?,?,?,?,?,?,?,'confirmee',?,?)")
                ->execute([unique_code('RES-', 3), $u['id'], $g['id'], $d['date'], $d['heure'] . ':00', $d['nb'], $total, $d['tel'] ?: null, $d['com'] ?: null]);
            $pdo->commit(); flash('Réservation confirmée ! Vous pouvez la payer et télécharger votre ticket.'); redirect('mes_reservations.php');
        } catch (RuntimeException $ex) { $pdo->rollBack(); flash($ex->getMessage(), 'err'); }
    } else $step = 'resume';
}
$total = (float)$g['tarif'] * max(1, $d['nb']);
page_start('Réserver : ' . $g['nom']);
?>
<?php if ($step === 'resume'): ?>
<div class="card narrow"><h2>Résumé de votre réservation</h2>
<p><b><?= e($g['emoji'] . ' ' . $g['nom']) ?></b><br>Le <?= e(date('d/m/Y', strtotime($d['date']))) ?> à <?= e($d['heure']) ?> · <?= (int)$d['nb'] ?> personne(s)<br>Contact : <?= e($u['prenom'] . ' ' . $u['nom']) ?>, <?= e($d['tel'] ?: 'téléphone non renseigné') ?>, <?= e($u['email']) ?></p>
<?php if ($d['com']): ?><p class="muted">Commentaire : <?= e($d['com']) ?></p><?php endif; ?>
<p class="total"><?= money($total) ?></p>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="confirm"><input type="hidden" name="id_jeu" value="<?= (int)$g['id'] ?>">
<input type="hidden" name="date_visite" value="<?= e($d['date']) ?>"><input type="hidden" name="heure" value="<?= e($d['heure']) ?>"><input type="hidden" name="nb_personnes" value="<?= (int)$d['nb'] ?>">
<input type="hidden" name="telephone" value="<?= e($d['tel']) ?>"><input type="hidden" name="commentaire" value="<?= e($d['com']) ?>">
<button class="btn">Confirmer la réservation</button> <a class="btn alt" href="<?= url('reserver.php?jeu=' . (int)$g['id']) ?>">Modifier</a></form></div>
<?php else: ?>
<div class="card narrow" style="max-width:680px"><h2><?= e($g['emoji'] . ' ' . $g['nom']) ?></h2><p class="muted">Capacité : <?= (int)$g['capacite'] ?> personnes par créneau · <?= money($g['tarif']) ?> par personne</p>
<form method="post" class="formgrid" data-calc data-unit="<?= e($g['tarif']) ?>"><?= csrf_field() ?><input type="hidden" name="id_jeu" value="<?= (int)$g['id'] ?>">
<div><label>Date</label><input type="date" name="date_visite" min="<?= date('Y-m-d') ?>" value="<?= e($d['date']) ?>" required></div>
<div><label>Heure</label><select name="heure"><?php foreach ($creneaux as $c): ?><option <?= $c === $d['heure'] ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
<div><label>Nombre de personnes</label><input type="number" name="nb_personnes" min="1" max="<?= (int)$g['capacite'] ?>" value="<?= (int)$d['nb'] ?>" required></div>
<div><label>Téléphone</label><input name="telephone" value="<?= e($d['tel']) ?>"></div>
<div class="full"><label>Commentaire (facultatif)</label><textarea name="commentaire" rows="2" maxlength="255"><?= e($d['com']) ?></textarea></div>
<div class="full"><span class="total" id="total"></span></div><div class="full"><button class="btn">Voir le résumé</button></div></form></div>
<?php endif; page_end();
