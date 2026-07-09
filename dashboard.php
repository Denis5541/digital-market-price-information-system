<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require_once __DIR__ . '/includes/header.php';
$me = current_user();
?>
<div class="dash-header">
    <div class="container">
        <div class="eyebrow">Dashboard</div>
        <h1>Welcome, <?= htmlspecialchars($me['name']) ?></h1>
        <p>You're signed in as <span class="badge badge-<?= $me['role'] ?>"><?= htmlspecialchars($me['role']) ?></span></p>
    </div>
</div>

<div class="container">
    <?php if (isset($_GET['error']) && $_GET['error'] === 'forbidden'): ?>
        <div class="alert alert-error">You don't have access to that page.</div>
    <?php endif; ?>

    <div class="panel-grid" id="statGrid">
        <div class="card stat-card"><div class="stat-label">Commodities tracked</div><div class="stat-value" id="statProducts">—</div></div>
        <div class="card stat-card"><div class="stat-label">Biggest mover today</div><div class="stat-value" id="statMover" style="font-size:1.2rem;">—</div></div>
        <?php if (in_array($me['role'], ['trader', 'farmer'], true)): ?>
        <div class="card stat-card"><div class="stat-label">Open negotiations</div><div class="stat-value" id="statOffers">—</div></div>
        <?php endif; ?>
        <?php if ($me['role'] === 'admin'): ?>
        <div class="card stat-card"><div class="stat-label">Registered users</div><div class="stat-value" id="statUsers">—</div></div>
        <?php endif; ?>
    </div>
</div>

<div class="dash-section">
    <div class="container">
        <div class="toolbar">
            <h2 class="mt-0">Live market board</h2>
            <a href="/market.php" class="btn btn-outline btn-sm">View full market →</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Commodity</th><th>Category</th><th>Price</th><th>Change</th><th>Updated</th></tr></thead>
                <tbody id="dashPriceRows"><tr><td colspan="5" class="text-faint">Loading…</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($me['role'] === 'farmer'): ?>
<div class="dash-section">
    <div class="container">
        <div class="card">
            <h3 class="mt-0">Selling tip</h3>
            <p>Compare today's price against the recent trend on the <a href="<?= $baseUrl ?>/market.php">Market page</a> before accepting an offer. A short-term dip doesn't always mean the season price is falling — check the chart.</p>
            <a href="<?= $baseUrl ?>/offers.php" class="btn btn-primary btn-sm">Review negotiations →</a>
        </div>
    </div>
</div>
<?php elseif ($me['role'] === 'trader'): ?>
<div class="dash-section">
    <div class="container">
        <div class="card">
            <h3 class="mt-0">Source supply directly</h3>
            <p>Open a negotiation with any registered farmer for a commodity and quantity you need.</p>
            <a href="<?= $baseUrl ?>/offers.php" class="btn btn-primary btn-sm">Start a negotiation →</a>
        </div>
    </div>
</div>
<?php elseif ($me['role'] === 'admin'): ?>
<div class="dash-section">
    <div class="container">
        <div class="card">
            <h3 class="mt-0">Administration</h3>
            <p>Manage the commodity catalogue, force a market price update, or oversee user accounts.</p>
            <div class="hero-actions">
                <a href="<?= $baseUrl ?>/products.php" class="btn btn-primary btn-sm">Manage commodities</a>
                <a href="<?= $baseUrl ?>/users.php" class="btn btn-outline btn-sm">Manage users</a>
                <button id="runSimBtn" class="btn btn-outline btn-sm">Run market update now</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
async function loadDashboard() {
    try {
        const { prices } = await api('/api/prices.php');
        document.getElementById('statProducts').textContent = prices.length;
        let mover = null;
        for (const p of prices) {
            if (mover === null || Math.abs(p.change_percent) > Math.abs(mover.change_percent)) mover = p;
        }
        if (mover) document.getElementById('statMover').innerHTML = escapeHtml(mover.name) + ' ' + formatChange(mover.change_percent);

        const rows = prices.slice(0, 8).map(p => `
            <tr>
                <td>${escapeHtml(p.name)}</td>
                <td class="text-dim">${escapeHtml(p.category)}</td>
                <td class="price-cell">$${Number(p.price).toFixed(2)}/${escapeHtml(p.unit)}</td>
                <td>${formatChange(p.change_percent)}</td>
                <td class="text-faint">${timeAgo(p.recorded_at)}</td>
            </tr>`).join('');
        document.getElementById('dashPriceRows').innerHTML = rows || '<tr><td colspan="5" class="text-faint">No data yet.</td></tr>';
    } catch (e) { /* toast already shown */ }

    <?php if (in_array($me['role'], ['trader', 'farmer'], true)): ?>
    try {
        const { threads } = await api('/api/offers.php');
        const openCount = threads.filter(t => t[t.length - 1].status === 'pending').length;
        document.getElementById('statOffers').textContent = openCount;
    } catch (e) {}
    <?php endif; ?>

    <?php if ($me['role'] === 'admin'): ?>
    try {
        const { users } = await api('/api/users.php');
        document.getElementById('statUsers').textContent = users.length;
    } catch (e) {}
    <?php endif; ?>
}
loadDashboard();

<?php if ($me['role'] === 'admin'): ?>
document.getElementById('runSimBtn').addEventListener('click', async (e) => {
    e.target.disabled = true;
    e.target.textContent = 'Updating…';
    try {
        const data = await api('/api/simulate.php', { method: 'POST', body: {} });
        showToast(data.message, 'success');
        loadDashboard();
    } catch (err) {}
    e.target.disabled = false;
    e.target.textContent = 'Run market update now';
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
