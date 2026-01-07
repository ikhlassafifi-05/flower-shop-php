<?php
session_start();
// require 'config/database.php';   // Connexion PDO
// require 'includes/auth.php';  // (optionnel si tu l'as)

$loginError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {

        $stmt = $pdo->prepare("SELECT id, name, role, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {

            // Création de la session
            $_SESSION['user'] = [
                'id'   => $user['id'],
                'name' => $user['name'],
                'role' => $user['role']
            ];

            // Redirection selon le rôle
            if ($user['role'] === 'admin') {
                header("Location: admin-bouquets.php");
            } else {
                header("Location: index.php");
            }
            exit;

        } else {
            $loginError = "Email ou mot de passe incorrect.";
        }

    } else {
        $loginError = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Coquettes Bouquets</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS -->
    <link rel="stylesheet" href="login.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
</head>
<body>

<div class="login-container">
    <h1 class="logo">❀ Bienvenue ❀</h1>

    <form id="loginForm" action="login.php" method="POST">

        <input
            type="email"
            id="loginEmail"
            name="email"
            placeholder="Entrez votre email"
            required
        >

        <input
            type="password"
            id="loginPassword"
            name="password"
            placeholder="Entrez votre mot de passe"
            required
        >

        <button type="submit">Se connecter</button>
    </form>

    <div class="login-footer">
        <span>Pas de compte ?</span>
        <button id="goCreate" type="button">Créer un compte</button>
    </div>

    <div id="loginResult">
        <?php if (!empty($loginError)): ?>
            <p class="error"><?= htmlspecialchars($loginError) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- JS -->
<script src="js/login.js"></script>

</body>
</html>
