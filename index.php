<?php
$pageTitle = 'Home';
$activeNav = 'home';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/market_engine.php';
auto_refresh_prices_if_stale();

$preview = db()->query("
    SELECT p.name, p.unit, pr.price, pr.change_percent
    FROM products p
    LEFT JOIN prices pr ON pr.id = (SELECT id FROM prices WHERE product_id = p.id ORDER BY recorded_at DESC, id DESC LIMIT 1)
    ORDER BY p.category, p.name
")->fetchAll();
?>

<div class="ticker-wrap">
    <div class="ticker-track" id="tickerTrack">
        <?php
        $renderTicker = function ($rows) {
            foreach ($rows as $r) {
                $cls = $r['change_percent'] > 0 ? 'ticker-up' : ($r['change_percent'] < 0 ? 'ticker-down' : '');
                $arrow = $r['change_percent'] > 0 ? '▲' : ($r['change_percent'] < 0 ? '▼' : '—');
                printf(
                    '<span class="ticker-item"><span class="name">%s</span> $%.2f/%s <span class="%s">%s %+0.2f%%</span></span>',
                    htmlspecialchars($r['name']), $r['price'], htmlspecialchars($r['unit']), $cls, $arrow, $r['change_percent']
                );
            }
        };
        $renderTicker($preview);
        $renderTicker($preview); // duplicate for seamless loop
        ?>
    </div>
</div>

<section class="hero">
    <div class="container hero-grid">
        <div>
            <div class="eyebrow">Digital Market Price Information System</div>
            <h1>Know the price before you sell.</h1>
            <p class="lead">AgriMarket gives farmers, traders and consumers a shared, live view of world commodity prices — and a direct line to negotiate fair deals, without the middleman holding all the information.</p>
            <div class="hero-actions">
                <a href="<?= $baseUrl ?>/register.php" class="btn btn-primary">Create a free account</a>
                <a href="<?= $baseUrl ?>/login.php" class="btn btn-outline">Log in</a>
            </div>
        </div>
        <div class="card">
            <h3 class="mt-0">Today's market snapshot</h3>
            <div class="table-wrap" style="border:none;">
                <table>
                    <thead><tr><th>Commodity</th><th>Price</th><th>24h</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($preview, 0, 6) as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['name']) ?></td>
                            <td class="price-cell">$<?= number_format($r['price'], 2) ?>/<?= htmlspecialchars($r['unit']) ?></td>
                            <td class="<?= $r['change_percent'] > 0 ? 'change-up' : ($r['change_percent'] < 0 ? 'change-down' : '') ?>">
                                <?= ($r['change_percent'] > 0 ? '▲ +' : ($r['change_percent'] < 0 ? '▼ ' : '— ')) . number_format($r['change_percent'], 2) ?>%
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-faint" style="margin-top:14px; font-size:0.8rem;">Sign in for full history, charts and negotiation tools.</p>
        </div>
    </div>
</section>

<section>
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">Why it matters</div>
            <h2>Information asymmetry is the oldest trick in the market.</h2>
            <p style="max-width:640px;">Farmers often sell below the world price simply because they can't see it. AgriMarket closes that gap with three simple tools.</p>
        </div>
        <div class="feature-grid">
            <div class="card feature-card">
                <div class="icon">◷</div>
                <h3>Live price board</h3>
                <p>World prices for cocoa, coffee, cotton, rubber, palm oil, grains and more, updated continuously and tracked over time.</p>
            </div>
            <div class="card feature-card">
                <div class="icon">⇄</div>
                <h3>Direct negotiation</h3>
                <p>Traders propose a price for a quantity. Farmers accept, reject, or counter — like a transparent, structured auction.</p>
            </div>
            <div class="card feature-card">
                <div class="icon">◈</div>
                <h3>Built for every role</h3>
                <p>Farmers study the market before selling. Traders source supply efficiently. Admins keep the commodity catalogue accurate.</p>
            </div>
        </div>
    </div>
</section>

<section class="alt">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">How it works</div>
            <h2>From price discovery to a signed deal.</h2>
        </div>
        <div class="feature-grid">
            <div class="card feature-card"><h3>1. Create an account</h3><p>Register as a Farmer or a Trader in under a minute.</p></div>
            <div class="card feature-card"><h3>2. Watch the market</h3><p>Open your dashboard to see live prices and trends for the commodities you care about.</p></div>
            <div class="card feature-card"><h3>3. Negotiate a deal</h3><p>Traders send an offer; farmers respond. Every counter-offer is tracked in one thread.</p></div>
        </div>
    </div>
</section>
</body>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
