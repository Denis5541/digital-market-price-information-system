<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$pageTitle = 'Market';
$activeNav = 'market';
require_once __DIR__ . '/includes/header.php';
?>
<div class="dash-header">
    <div class="container">
        <div class="eyebrow">World market</div>
        <h1>Live commodity prices</h1>
        <p>Prices fluctuate automatically to reflect realistic market movement. Click a row to see its trend.</p>
    </div>
</div>

<div class="container">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Commodity</th><th>Category</th><th>Price</th><th>24h change</th><th>Last update</th></tr></thead>
            <tbody id="priceRows"><tr><td colspan="5" class="text-faint">Loading…</td></tr></tbody>
        </table>
    </div>

    <div class="card" style="margin-top:24px;">
        <div class="flex-between">
            <h3 class="mt-0" id="chartTitle">Select a commodity</h3>
            <span class="text-faint" id="chartSubtitle" style="font-size:0.85rem;"></span>
        </div>
        <canvas id="priceChart" height="90"></canvas>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<script>
let chart = null;
let selectedId = null;

function renderTable(prices) {
    const rows = prices.map(p => `
        <tr style="cursor:pointer" onclick="selectProduct(${p.id}, '${escapeHtml(p.name)}', '${escapeHtml(p.unit)}')">
            <td>${escapeHtml(p.name)}</td>
            <td class="text-dim">${escapeHtml(p.category)}</td>
            <td class="price-cell">$${Number(p.price).toFixed(2)}/${escapeHtml(p.unit)}</td>
            <td>${formatChange(p.change_percent)}</td>
            <td class="text-faint">${timeAgo(p.recorded_at)}</td>
        </tr>`).join('');
    document.getElementById('priceRows').innerHTML = rows || '<tr><td colspan="5" class="text-faint">No data yet.</td></tr>';
}

async function loadPrices() {
    try {
        const { prices } = await api('/api/prices.php');
        renderTable(prices);
        if (selectedId === null && prices.length) {
            selectProduct(prices[0].id, prices[0].name, prices[0].unit);
        }
    } catch (e) {}
}

async function selectProduct(id, name, unit) {
    selectedId = id;
    document.getElementById('chartTitle').textContent = name + ' — price history';
    document.getElementById('chartSubtitle').textContent = 'per ' + unit;
    try {
        const { history } = await api('/api/prices.php?product_id=' + id);
        const labels = history.map(h => {
            const d = new Date(h.recorded_at.replace(' ', 'T') + 'Z');
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        });
        const data = history.map(h => h.price);

        if (chart) chart.destroy();
        const ctx = document.getElementById('priceChart').getContext('2d');
        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: name,
                    data,
                    borderColor: '#E7B23D',
                    backgroundColor: 'rgba(231,178,61,0.12)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 2,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#9DB3A4' }, grid: { color: '#2E4738' } },
                    y: { ticks: { color: '#9DB3A4' }, grid: { color: '#2E4738' } }
                }
            }
        });
    } catch (e) {}
}

loadPrices();
setInterval(loadPrices, 30000); // refresh table every 30s
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
