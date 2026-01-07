<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Récupérer les filtres depuis l'URL ou les cookies
$couleur = $_GET['couleur'] ?? $_COOKIE['filtre_couleur'] ?? '';
$prix_max = $_GET['prix_max'] ?? $_COOKIE['filtre_prix'] ?? '';
$saison = $_GET['saison'] ?? $_COOKIE['filtre_saison'] ?? '';

// Enregistrer les filtres dans un cookie (valide 7 jours)
if (isset($_GET['couleur']) || isset($_GET['prix_max']) || isset($_GET['saison'])) {
    setcookie('filtre_couleur', $couleur, time() + 604800, '/');
    setcookie('filtre_prix', $prix_max, time() + 604800, '/');
    setcookie('filtre_saison', $saison, time() + 604800, '/');
}

// Construire la requête avec les filtres
$sql = "SELECT * FROM products WHERE 1=1";
$params = [];
$types = "";

if (!empty($couleur)) {
    $sql .= " AND color = ?";
    $params[] = $couleur;
    $types .= "s";
}

if (!empty($prix_max) && is_numeric($prix_max)) {
    $sql .= " AND price <= ?";
    $params[] = $prix_max;
    $types .= "d";
}

if (!empty($saison)) {
    $sql .= " AND season = ?";
    $params[] = $saison;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

// Exécuter la requête préparée
$stmt = $pdo->prepare($sql);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$bouquets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les valeurs uniques pour les filtres (pour les dropdowns)
$stmt = $pdo->query("SELECT DISTINCT color FROM products WHERE color IS NOT NULL ORDER BY color");
$couleurs = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->query("SELECT DISTINCT season FROM products WHERE season IS NOT NULL ORDER BY season");
$saisons = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue - Coquette Bouquet</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Style coquette */
        :root {
            --rose-pastel: #ffd6e7;
            --lavande: #e6e6fa;
            --pale-mint: #c7f0d2;
            --blush: #f9c5d1;
            --vintage-rose: #b87c7c;
        }
        body {
            background-color: #fff9fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #555;
        }
        .header {
            background: linear-gradient(135deg, var(--rose-pastel), var(--lavande));
            padding: 2rem;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 4px 20px rgba(184, 124, 124, 0.1);
        }
        .filters {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin: 2rem auto;
            max-width: 1200px;
            box-shadow: 0 5px 15px rgba(255, 214, 231, 0.3);
            border: 1px solid var(--rose-pastel);
        }
        .filter-group {
            display: inline-block;
            margin-right: 1.5rem;
            margin-bottom: 1rem;
        }
        label {
            display: block;
            color: var(--vintage-rose);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        select, input {
            padding: 0.7rem 1rem;
            border: 2px solid var(--blush);
            border-radius: 12px;
            background: #fff;
            color: #666;
            font-size: 1rem;
            transition: all 0.3s;
        }
        select:focus, input:focus {
            outline: none;
            border-color: var(--vintage-rose);
            box-shadow: 0 0 0 3px rgba(184, 124, 124, 0.2);
        }
        .btn-filter {
            background: var(--blush);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        .btn-filter:hover {
            background: var(--vintage-rose);
        }
        .btn-reset {
            background: transparent;
            color: var(--vintage-rose);
            border: 2px solid var(--blush);
            padding: 0.8rem 2rem;
            border-radius: 12px;
            cursor: pointer;
            font-weight: bold;
            margin-left: 1rem;
            transition: all 0.3s;
        }
        .btn-reset:hover {
            background: var(--rose-pastel);
        }
        .bouquet-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .bouquet-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(255, 214, 231, 0.3);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid var(--rose-pastel);
        }
        .bouquet-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(255, 214, 231, 0.5);
        }
        .bouquet-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-bottom: 3px solid var(--blush);
        }
        .bouquet-info {
            padding: 1.5rem;
        }
        .bouquet-title {
            font-size: 1.3rem;
            color: var(--vintage-rose);
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        .bouquet-description {
            color: #888;
            font-size: 0.95rem;
            margin-bottom: 1rem;
            line-height: 1.4;
        }
        .bouquet-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .bouquet-price {
            color: var(--vintage-rose);
            font-size: 1.5rem;
            font-weight: bold;
        }
        .bouquet-color, .bouquet-season {
            background: var(--pale-mint);
            color: #5a7c6a;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .bouquet-season {
            background: var(--lavande);
            color: #6a6a8a;
        }
        .btn-add-cart {
            display: block;
            width: 100%;
            background: var(--rose-pastel);
            color: var(--vintage-rose);
            border: none;
            padding: 0.8rem;
            border-radius: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            text-align: center;
            text-decoration: none;
        }
        .btn-add-cart:hover {
            background: var(--blush);
            color: white;
        }
        .no-results {
            text-align: center;
            padding: 3rem;
            color: var(--vintage-rose);
            font-size: 1.2rem;
            grid-column: 1 / -1;
        }
        .header-title {
            color: var(--vintage-rose);
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .header-subtitle {
            text-align: center;
            color: #888;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="header">
        <h1 class="header-title">🌸 Catalogue Coquette Bouquet 🌸</h1>
        <p class="header-subtitle">Découvrez nos bouquets romantiques et pastels, faits pour chaque occasion spéciale.</p>
    </div>

    <div class="filters">
        <form method="GET" action="">
            <div class="filter-group">
                <label for="couleur">🎨 Couleur dominante</label>
                <select name="couleur" id="couleur">
                    <option value="">Toutes les couleurs</option>
                    <?php foreach ($couleurs as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $couleur == $c ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="prix_max">💰 Prix maximum (€)</label>
                <input type="number" name="prix_max" id="prix_max" min="0" step="5" 
                       value="<?= htmlspecialchars($prix_max) ?>" placeholder="Ex: 50">
            </div>

            <div class="filter-group">
                <label for="saison">🍂 Saison</label>
                <select name="saison" id="saison">
                    <option value="">Toutes les saisons</option>
                    <?php foreach ($saisons as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $saison == $s ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-filter">Filtrer ✨</button>
            <a href="catalogue.php" class="btn-reset">Réinitialiser</a>
        </form>
    </div>

    <div class="bouquet-grid">
        <?php if (count($bouquets) > 0): ?>
            <?php foreach ($bouquets as $bouquet): ?>
                <div class="bouquet-card">
                    <img src="uploads/<?= htmlspecialchars($bouquet['image_url']) ?>" 
                         alt="<?= htmlspecialchars($bouquet['name']) ?>" class="bouquet-image">
                    <div class="bouquet-info">
                        <h3 class="bouquet-title"><?= htmlspecialchars($bouquet['name']) ?></h3>
                        <p class="bouquet-description"><?= htmlspecialchars($bouquet['description']) ?></p>
                        
                        <div class="bouquet-meta">
                            <span class="bouquet-color"><?= htmlspecialchars($bouquet['color']) ?></span>
                            <span class="bouquet-season"><?= htmlspecialchars($bouquet['season']) ?></span>
                        </div>
                        
                        <div class="bouquet-price"><?= number_format($bouquet['price'], 2) ?> €</div>
                        
                        <a href="panier.php?action=add&id=<?= $bouquet['id'] ?>" 
                           class="btn-add-cart">Ajouter au panier 💐</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-results">
                <p>Aucun bouquet ne correspond à vos critères de filtrage.</p>
                <p>Essayez d'élargir vos filtres ou découvrez d'autres merveilles !</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>