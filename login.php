<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

if (user()) redirect('index.php');

$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';

    $u = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND statut = 'actif'");
    $u->execute([$email]);
    $user = $u->fetch();

    if ($user && check_password($pass, (string)$user['mot_de_passe'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$user['id'];
        flash('Connexion réussie !');
        $next = $_SESSION['next'] ?? null; unset($_SESSION['next']);
        if ($next && $user['role'] === 'client' && str_starts_with($next, BASE_URL . '/')) redirect(substr($next, strlen(BASE_URL)));
        redirect(home_for($user['role']));
    } else {
        $err = "Email ou mot de passe incorrect.";
    }
}

page_start('Connexion');
?>
<div class="wrap content">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-icon">🔐</div>
      <h2>Connexion</h2>
      <p>Accédez à votre espace ParcJeux</p>
    </div>

    <?php if ($err): ?>
      <div class="alert err"><?= e($err) ?></div>
    <?php endif; ?>

    <form method="post" class="auth-form">
      <div class="form-group">
        <label for="email">Adresse Email</label>
        <input type="email" id="email" name="email" required placeholder="votre@email.com" value="<?= e($_POST['email'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" required placeholder="••••••••">
      </div>

      <div class="auth-actions">
        <button type="submit" class="btn">Se connecter</button>
        <a class="btn alt" href="<?= url('register.php') ?>">Créer un compte</a>
      </div>
    </form>
  </div>
</div>
<?php page_end(); ?>