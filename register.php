<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

if (user()) redirect('index.php');

$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nom = trim($_POST['nom'] ?? '');
  $prenom = trim($_POST['prenom'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $tel = trim($_POST['telephone'] ?? '');
  $p1 = $_POST['password'] ?? '';
  $p2 = $_POST['password_confirm'] ?? '';

  if (!$nom || !$prenom || !$email || !$p1) {
    $err = "Veuillez remplir tous les champs obligatoires.";
  } elseif ($tel !== '' && !preg_match('/^[0-9]{1,10}$/', $tel)) {
    $err = "Le téléphone doit contenir uniquement des chiffres (10 maximum).";
  } elseif ($p1 !== $p2) {
    $err = "Les mots de passe ne correspondent pas.";
  } else {
    $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
      $err = "Cet email est déjà utilisé.";
    } else {
      $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, telephone, mot_de_passe, role) VALUES (?, ?, ?, ?, ?, 'client')");
      $stmt->execute([$nom, $prenom, $email, $tel, $p1]);
      session_regenerate_id(true);
      $_SESSION['uid'] = (int)$pdo->lastInsertId();
      flash('Compte créé avec succès !');
      redirect('index.php');
    }
  }
}

page_start('Inscription');
?>
<div class="wrap content">
  <div class="auth-card wide">
    <div class="auth-header">
      <div class="auth-icon">✨</div>
      <h2>Créer un compte</h2>
      <p>Rejoignez ParcJeux pour réserver vos billets facilement</p>
    </div>

    <?php if ($err): ?>
      <div class="alert err"><?= e($err) ?></div>
    <?php endif; ?>

    <form method="post" class="auth-form">
      <div class="formgrid">
        <div>
          <label for="nom">Nom *</label>
          <input type="text" id="nom" name="nom" required placeholder="Votre nom" value="<?= e($_POST['nom'] ?? '') ?>">
        </div>
        <div>
          <label for="prenom">Prénom *</label>
          <input type="text" id="prenom" name="prenom" required placeholder="Votre prénom" value="<?= e($_POST['prenom'] ?? '') ?>">
        </div>
        <div>
          <label for="email">Email *</label>
          <input type="email" id="email" name="email" required placeholder="votre@email.com" value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div>
          <label for="telephone">Téléphone</label>
          <input type="tel" id="telephone" name="telephone" placeholder="06XXXXXXXX" inputmode="numeric" maxlength="10" pattern="[0-9]{1,10}" title="10 chiffres maximum" oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)" value="<?= e($_POST['telephone'] ?? '') ?>">
        </div>
        <div>
          <label for="password">Mot de passe *</label>
          <input type="password" id="password" name="password" required placeholder="••••••••">
        </div>
        <div>
          <label for="password_confirm">Confirmation *</label>
          <input type="password" id="password_confirm" name="password_confirm" required placeholder="••••••••">
        </div>
      </div>

      <div class="auth-actions">
        <button type="submit" class="btn">Créer mon compte</button>
        <a class="btn alt" href="<?= url('login.php') ?>">Déjà un compte ?</a>
      </div>
    </form>
  </div>
</div>
<?php page_end(); ?>