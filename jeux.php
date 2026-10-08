<?php
require __DIR__ . '/includes/bootstrap.php'; 
require __DIR__ . '/includes/layout.php'; 
require __DIR__ . '/includes/cards.php';

$q = trim($_GET['q'] ?? ''); 
$cat = $_GET['cat'] ?? ''; 
$etat = $_GET['etat'] ?? '';

$sql = 'SELECT * FROM jeux WHERE 1'; 
$p = [];

if ($q !== '') { 
    $sql .= ' AND (nom LIKE ? OR description LIKE ?)'; 
    $p[] = "%$q%"; 
    $p[] = "%$q%"; 
}

if ($cat !== '') { 
    $sql .= ' AND categorie = ?'; 
    $p[] = $cat; 
}

if (isset(ETATS[$etat])) { 
    $sql .= ' AND etat = ?'; 
    $p[] = $etat; 
}

$sql .= ' ORDER BY nom';
$st = $pdo->prepare($sql); 
$st->execute($p); 
$jeux = $st->fetchAll();

$cats = $pdo->query('SELECT DISTINCT categorie FROM jeux ORDER BY 1')->fetchAll(PDO::FETCH_COLUMN);

page_start('Nos jeux');
?>

<div class="wrap content">
    <form class="filters" method="get">
      <div>
          <label>Recherche</label>
          <input name="q" value="<?= e($q) ?>" placeholder="Nom ou mot-clé">
      </div>
      <div>
          <label>Catégorie</label>
          <select name="cat">
              <option value="">Toutes</option>
              <?php foreach ($cats as $c): ?>
                  <option <?= $c === $cat ? 'selected' : '' ?>><?= e($c) ?></option>
              <?php endforeach; ?>
          </select>
      </div>
      <div>
          <label>État</label>
          <select name="etat">
              <option value="">Tous</option>
              <?php foreach (ETATS as $k => $v): ?>
                  <option value="<?= e($k) ?>" <?= $k === $etat ? 'selected' : '' ?>><?= e($v) ?></option>
              <?php endforeach; ?>
          </select>
      </div>
      <button type="submit" class="btn">Filtrer</button>
    </form>

    <?php if (!$jeux): ?>
        <p class="muted">Aucun jeu ne correspond à votre recherche.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($jeux as $g): ?>
                <?= game_card($g) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php page_end(); ?>