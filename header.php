<header>
    <nav>
        <a href="catalogue.php" class="logo">🌸 Coquette Bouquet</a>
        <div class="nav-links">
            <a href="catalogue.php">Catalogue</a>
            <a href="panier.php">Panier (<?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?>)</a>
            <a href="mes-commandes.php">Mes commandes</a>
            <a href="logout.php">Déconnexion</a>
        </div>
    </nav>
</header>