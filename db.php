<?php
/**
 * Database layer: opens (and if needed, creates + seeds) the SQLite database.
 * Using PDO with prepared statements everywhere -> protects against SQL injection.
 */
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $isNew = !file_exists(DB_PATH);

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    bootstrap_schema($pdo);

    if ($isNew) {
        seed_database($pdo);
    }

    return $pdo;
}

function bootstrap_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL CHECK(role IN ('admin','trader','farmer')),
            phone TEXT,
            location TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            category TEXT NOT NULL,
            unit TEXT NOT NULL,
            base_price REAL NOT NULL,
            volatility REAL NOT NULL DEFAULT 1.5,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS prices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
            price REAL NOT NULL,
            change_percent REAL NOT NULL DEFAULT 0,
            recorded_at TEXT NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS offers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
            trader_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            farmer_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            quantity REAL NOT NULL,
            unit TEXT NOT NULL,
            price REAL NOT NULL,
            status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending','accepted','rejected','countered','withdrawn')),
            parent_offer_id INTEGER REFERENCES offers(id) ON DELETE SET NULL,
            root_offer_id INTEGER,
            initiated_by TEXT NOT NULL CHECK(initiated_by IN ('trader','farmer')),
            message TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        );

        CREATE INDEX IF NOT EXISTS idx_prices_product ON prices(product_id, recorded_at);
        CREATE INDEX IF NOT EXISTS idx_offers_trader ON offers(trader_id);
        CREATE INDEX IF NOT EXISTS idx_offers_farmer ON offers(farmer_id);
        CREATE INDEX IF NOT EXISTS idx_offers_root ON offers(root_offer_id);
    ");
}

function seed_database(PDO $pdo): void
{
    // --- Demo users (one per role) ---
    $users = [
        ['Admin User',  'admin@agrimarket.test',  'Admin123!',  'admin',  null,            'Yaoundé HQ'],
        ['Paul Etoa',   'trader@agrimarket.test', 'Trader123!', 'trader', '+237600000001', 'Douala'],
        ['Marie Nkomo', 'farmer@agrimarket.test', 'Farmer123!', 'farmer', '+237600000002', 'Bafoussam'],
    ];
    $stmt = $pdo->prepare(
        "INSERT INTO users (name, email, password_hash, role, phone, location) VALUES (?, ?, ?, ?, ?, ?)"
    );
    foreach ($users as $u) {
        $stmt->execute([$u[0], $u[1], password_hash($u[2], PASSWORD_DEFAULT), $u[3], $u[4], $u[5]]);
    }

    // --- Commodities traded on world markets, relevant to farmers/traders ---
    // base_price is a realistic illustrative reference price; volatility drives simulated fluctuation.
    $products = [
        ['Cocoa',          'Cash Crop',   'kg',  2.85, 2.2],
        ['Coffee Arabica', 'Cash Crop',   'kg',  6.10, 2.6],
        ['Coffee Robusta', 'Cash Crop',   'kg',  2.95, 2.0],
        ['Cotton',         'Fiber',       'kg',  1.75, 1.8],
        ['Natural Rubber', 'Industrial',  'kg',  1.55, 2.4],
        ['Palm Oil',       'Oilseed',     'kg',  0.95, 2.1],
        ['Maize',          'Cereal',      'kg',  0.28, 1.4],
        ['Rice (Paddy)',   'Cereal',      'kg',  0.42, 1.1],
        ['Wheat',          'Cereal',      'kg',  0.31, 1.6],
        ['Cassava',        'Tuber',       'kg',  0.18, 1.0],
        ['Plantain',       'Fruit',       'kg',  0.35, 1.3],
        ['White Sugar',    'Sweetener',   'kg',  0.52, 1.7],
    ];
    $stmt = $pdo->prepare(
        "INSERT INTO products (name, category, unit, base_price, volatility) VALUES (?, ?, ?, ?, ?)"
    );
    foreach ($products as $p) {
        $stmt->execute($p);
    }

    // --- Seed initial price history (today's opening price) ---
    $ids = $pdo->query("SELECT id, base_price FROM products")->fetchAll();
    $priceStmt = $pdo->prepare(
        "INSERT INTO prices (product_id, price, change_percent, recorded_at) VALUES (?, ?, 0, datetime('now'))"
    );
    foreach ($ids as $row) {
        $priceStmt->execute([$row['id'], $row['base_price']]);
    }
}
