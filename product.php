<?php
require_once __DIR__ . '/includes/auth.php';
require_role('admin');
$pageTitle = 'Commodities';
$activeNav = 'products';
require_once __DIR__ . '/includes/header.php';
?>
<div class="dash-header">
    <div class="container">
        <div class="eyebrow">Administration</div>
        <h1>Commodity catalogue</h1>
        <p>Add, edit or remove the commodities tracked on the market board. Volatility controls how strongly a commodity's simulated price fluctuates.</p>
    </div>
</div>

<div class="container">
    <div class="toolbar">
        <h2 class="mt-0">All commodities</h2>
        <button class="btn btn-primary btn-sm" id="addBtn">+ Add commodity</button>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Category</th><th>Unit</th><th>Base price</th><th>Volatility</th><th>Latest price</th><th></th></tr></thead>
            <tbody id="rows"><tr><td colspan="7" class="text-faint">Loading…</td></tr></tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="modal">
    <div class="modal">
        <span class="modal-close" id="closeModal">×</span>
        <h3 class="mt-0" id="modalTitle">Add commodity</h3>
        <div id="modalAlert"></div>
        <form id="form">
            <input type="hidden" id="pid">
            <div class="field"><label for="name">Name</label><input type="text" id="name" required></div>
            <div class="field"><label for="category">Category</label><input type="text" id="category" required></div>
            <div class="field"><label for="unit">Unit</label><input type="text" id="unit" placeholder="kg, ton…" required></div>
            <div class="field"><label for="base_price">Base price ($)</label><input type="number" id="base_price" min="0.01" step="0.01" required></div>
            <div class="field"><label for="volatility">Volatility (0–10)</label><input type="number" id="volatility" min="0" max="10" step="0.1" required></div>
            <button type="submit" class="btn btn-primary btn-block">Save</button>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById('modal');
function openModal(p = null) {
    document.getElementById('modalAlert').innerHTML = '';
    document.getElementById('modalTitle').textContent = p ? 'Edit commodity' : 'Add commodity';
    document.getElementById('pid').value = p ? p.id : '';
    document.getElementById('name').value = p ? p.name : '';
    document.getElementById('category').value = p ? p.category : '';
    document.getElementById('unit').value = p ? p.unit : '';
    document.getElementById('base_price').value = p ? p.base_price : '';
    document.getElementById('volatility').value = p ? p.volatility : '1.5';
    modal.classList.add('open');
}
document.getElementById('addBtn').addEventListener('click', () => openModal());
document.getElementById('closeModal').addEventListener('click', () => modal.classList.remove('open'));
modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('open'); });

async function loadProducts() {
    try {
        const { products } = await api('/api/products.php');
        document.getElementById('rows').innerHTML = products.map(p => `
            <tr>
                <td>${escapeHtml(p.name)}</td>
                <td class="text-dim">${escapeHtml(p.category)}</td>
                <td>${escapeHtml(p.unit)}</td>
                <td class="price-cell">$${Number(p.base_price).toFixed(2)}</td>
                <td>${p.volatility}</td>
                <td class="price-cell">${p.latest_price ? '$' + Number(p.latest_price).toFixed(2) : '—'}</td>
                <td>
                    <button class="btn btn-outline btn-sm" onclick='editProduct(${JSON.stringify(p).replace(/'/g, "&#39;")})'>Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteProduct(${p.id}, '${escapeHtml(p.name)}')">Delete</button>
                </td>
            </tr>`).join('') || '<tr><td colspan="7" class="text-faint">No commodities yet.</td></tr>';
    } catch (e) {}
}
function editProduct(p) { openModal(p); }

async function deleteProduct(id, name) {
    if (!confirm(`Delete "${name}"? This also removes its price history.`)) return;
    try {
        await api('/api/products.php?id=' + id, { method: 'DELETE' });
        showToast('Commodity deleted.', 'success');
        loadProducts();
    } catch (e) {}
}

document.getElementById('form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const alertBox = document.getElementById('modalAlert');
    alertBox.innerHTML = '';
    const id = document.getElementById('pid').value;
    const payload = {
        name: document.getElementById('name').value,
        category: document.getElementById('category').value,
        unit: document.getElementById('unit').value,
        base_price: document.getElementById('base_price').value,
        volatility: document.getElementById('volatility').value
    };
    try {
        if (id) {
            payload.id = id;
            await api('/api/products.php', { method: 'PUT', body: payload });
        } else {
            await api('/api/products.php', { method: 'POST', body: payload });
        }
        showToast('Saved.', 'success');
        modal.classList.remove('open');
        loadProducts();
    } catch (err) {
        alertBox.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
    }
});

loadProducts();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
