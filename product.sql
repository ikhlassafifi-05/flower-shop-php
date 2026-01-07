-- Table products
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) UNIQUE NOT NULL,  
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    color VARCHAR(30) DEFAULT 'Mixte',
    season VARCHAR(30) DEFAULT 'Toute saison',
    category VARCHAR(50) DEFAULT 'Bouquet',
    stock INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertion des bouquets avec couleurs et saisons adaptées
INSERT INTO products (code, name, description, price, image_url, color, season) VALUES
('fp01', 'Sunny Delight Bouquet', 'A vibrant mix of dahlias, ranunculus, and cheerful poppies perfect for brightening any space.', 450.00, 'F3.jpeg', 'Jaune/Orange', 'Printemps'),
('fp02', 'Pink Perfection Carnations', 'Charming pink carnations paired with delicate daisies, a sweet gesture of affection.', 280.00, 'F4.jpeg', 'Rose', 'Printemps'),
('fp03', 'Spring Radiance Bouquet', 'Brighten any day with this joyful mix of ranunculus, daffodils, and fresh tulips.', 500.00, 'F6.jpeg', 'Multicolore', 'Printemps'),
('fp04', 'Rustic Pink Whisper', 'Soft pink gerberas, roses, and carnations lovingly wrapped in natural kraft paper for a touch of rustic elegance.', 380.00, 'F7.jpeg', 'Rose', 'Automne'),
('fp05', 'Pastel Dream Cloud', 'A dreamy confection of pastel lisianthus, carnations, and delphiniums, evoking the soft hues of a spring morning.', 520.00, 'F8.jpeg', 'Pastel', 'Printemps'),
('fp06', 'Ethereal White Charm', 'An elegant anthurium leads a chorus of white lilies, soft roses, and orchids, with delicate hints of sky blue.', 620.00, 'F11.jpeg', 'Blanc/Bleu', 'Hiver'),
('fp07', 'Royal Bloom Harmony', 'A striking composition of pure white roses and deep purple clematis, nestled with lush hydrangeas for a regal statement.', 680.00, 'F12.jpeg', 'Violet/Blanc', 'Été'),
('fp08', 'Joyful Sunshine Medley', 'Bursting with happiness! A vibrant medley of gerberas, roses, and daisies in cheerful yellows, oranges, and pinks.', 450.00, 'F15.jpeg', 'Orange/Rose', 'Été'),
('fp09', 'Wildflower Fiesta', 'A bold and beautiful fiesta of wildflowers, featuring vibrant irises, sunny yellows, and warm oranges, wrapped with rustic flair.', 490.00, 'F16.jpeg', 'Orange/Violet', 'Été'),
('fp10', 'Lavender Twilight Kiss', 'A dreamy escape into cool lavender and blue hues, featuring soft pink roses, purple lisianthus and delicate delphiniums.', 550.00, 'F10.jpeg', 'Lavande/Rose', 'Printemps'),
('fp11', 'Sweet Serenity Pink', 'An elegant and graceful arrangement of classic white lilies, soft pink gerberas, and delicate roses for serene moments.', 580.00, 'F13.jpeg', 'Rose/Blanc', 'Toute saison'),
('fp12', 'Azure & Peach Celebration', 'Celebrate in style with soft blue hydrangeas, peach roses, and creamy eustoma. A delightful gift for any occasion!', 599.99, 'F9.jpeg', 'Bleu/Pêche', 'Été');