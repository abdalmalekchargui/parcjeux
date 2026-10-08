<?php
require __DIR__ . '/../includes/bootstrap.php'; require __DIR__ . '/../includes/layout.php';
require_role('admin');
$one = fn(string $sql) => (float)$pdo->query($sql)->fetchColumn();
$jeux = (int)$one('SELECT COUNT(*) FROM jeux'); $dispo = (int)$one("SELECT COUNT(*) FROM jeux WHERE etat IN ('disponible','en_fonctionnement')");
$maint = (int)$one("SELECT COUNT(*) FROM jeux WHERE etat='en_maintenance'"); $indispo = $jeux - $dispo - $maint;
$rec = $one('SELECT COALESCE(SUM(montant),0) FROM recettes'); $dep = $one('SELECT COALESCE(SUM(montant),0) FROM depenses');
$cards = [['Jeux', $jeux, 0, '', '#6c4bd1'], ['Disponibles', $dispo, 0, '', '#12b5a6'], ['Indisponibles', $indispo, 0, '', '#e63946'], ['En maintenance', $maint, 0, '', '#f4a100'],
  ['Clients', (int)$one("SELECT COUNT(*) FROM utilisateurs WHERE role='client'"), 0, '', '#1f6feb'], ['Réservations', (int)$one("SELECT COUNT(*) FROM reservations WHERE statut<>'annulee'"), 0, '', '#d6409f'],
  ['Billets vendus', (int)$one('SELECT COUNT(*) FROM billets'), 0, '', '#6c4bd1'], ['Recettes', $rec, 2, ' MAD', '#12b5a6'], ['Dépenses', $dep, 2, ' MAD', '#e63946'], ['Résultat (recettes − dépenses)', $rec - $dep, 2, ' MAD', $rec - $dep >= 0 ? '#12b5a6' : '#e63946']];
$last = $pdo->query("SELECT r.*, CONCAT(u.prenom,' ',u.nom) client, j.nom jeu FROM reservations r JOIN utilisateurs u ON u.id=r.id_client JOIN jeux j ON j.id=r.id_jeu ORDER BY r.created_at DESC LIMIT 8")->fetchAll();
$rev = $pdo->query('SELECT date_prochaine_revision d, j.nom FROM maintenance m JOIN jeux j ON j.id=m.id_jeu WHERE date_prochaine_revision>=CURDATE() ORDER BY d LIMIT 3')->fetchAll();
page_start('Tableau de bord', 'admin');
?>
<div class="stats"><?php foreach ($cards as [$l, $v, $dec, $suf, $c]): ?><div class="stat" style="--c:<?= e($c) ?>"><small><?= e($l) ?></small><b data-count="<?= e($v) ?>" data-dec="<?= (int)$dec ?>" data-suffix="<?= e($suf) ?>">0</b></div><?php endforeach; ?></div>
<div class="filters noprint"><div style="max-width:220px"><label>Période des graphiques</label><select id="period"><option value="today">Aujourd'hui</option><option value="week">Cette semaine</option><option value="month" selected>Ce mois</option><option value="year">Cette année</option></select></div></div>
<div class="charts"><div class="card"><h3>Recettes vs dépenses</h3><canvas id="c1"></canvas></div><div class="card"><h3>Répartition des jeux par état</h3><canvas id="c3"></canvas></div></div>
<div class="charts"><div class="card"><h3>Réservations créées</h3><canvas id="c4"></canvas></div><div class="card"><h3>Popularité des jeux</h3><canvas id="c2"></canvas></div></div>
<?php if ($rev): ?><div class="alert warn">Révisions à venir : <?php foreach ($rev as $r) echo e($r['nom'] . ' (' . date('d/m', strtotime($r['d'])) . ') '); ?></div><?php endif; ?>
<h3>Dernières réservations</h3><div class="tablewrap"><table><tr><th>Client</th><th>Jeu</th><th>Date</th><th>Heure</th><th>Statut</th><th class="right">Montant</th></tr>
<?php foreach ($last as $r): ?><tr><td><?= e($r['client']) ?></td><td><?= e($r['jeu']) ?></td><td><?= e(date('d/m/Y', strtotime($r['date_visite']))) ?></td><td><?= e(substr($r['heure'], 0, 5)) ?></td><td><?= statut_badge($r['statut']) ?></td><td class="right"><?= money($r['montant_total']) ?></td></tr><?php endforeach; ?></table></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const charts = {}, pal = ['#6c4bd1', '#12b5a6', '#e63946', '#f4a100', '#1f6feb', '#d6409f'];
function draw(id, cfg) { if (charts[id]) charts[id].destroy(); charts[id] = new Chart(document.getElementById(id), cfg); }
async function load() {
  const d = await (await fetch('api_stats.php?period=' + document.getElementById('period').value)).json();
  const opt = {responsive: true, interaction: {mode: 'index', intersect: false}};
  draw('c1', {type: 'bar', data: {labels: d.labels, datasets: [{label: 'Recettes', data: d.recettes, backgroundColor: '#12b5a6'}, {label: 'Dépenses', data: d.depenses, backgroundColor: '#e63946'}]}, options: opt});
  draw('c4', {type: 'line', data: {labels: d.labels, datasets: [{label: 'Réservations', data: d.reservations, borderColor: '#6c4bd1', backgroundColor: '#6c4bd133', fill: true, tension: .3}]}, options: opt});
  draw('c2', {type: 'bar', data: {labels: d.popularite.labels, datasets: [{label: 'Billets + personnes réservées', data: d.popularite.data, backgroundColor: pal}]}, options: {indexAxis: 'y', responsive: true}});
  draw('c3', {type: 'doughnut', data: {labels: d.etats.labels, datasets: [{data: d.etats.data, backgroundColor: pal}]}, options: {responsive: true}});
}
document.getElementById('period').addEventListener('change', load); load();
</script>
<?php page_end('admin');
