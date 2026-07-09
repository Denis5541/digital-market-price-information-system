<?php
require_once __DIR__ . '/includes/auth.php';
require_role('trader', 'farmer', 'admin');
$pageTitle = 'Negotiations';
$activeNav = 'offers';
require_once __DIR__ . '/includes/header.php';
$me = current_user();
?>
<div class="dash-header">
    <div class="container">
        <div class="eyebrow">Negotiation room</div>
        <h1>Negotiations</h1>
        <p>
            <?php if ($me['role'] === 'trader'): ?>
                Propose a price and quantity directly to a farmer. They can accept, reject, or counter.
            <?php elseif ($me['role'] === 'farmer'): ?>
                Review offers from traders. Accept a fair price, reject, or send a counter-offer.
            <?php else: ?>
                Oversight view of every negotiation happening on the platform.
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="container">
    <?php if ($me['role'] === 'trader'): ?>
    <div class="toolbar">
        <h2 class="mt-0">Your negotiations</h2>
        <button class="btn btn-primary btn-sm" id="newOfferBtn">+ New offer to a farmer</button>
    </div>
    <?php else: ?>
    <h2>Your negotiations</h2>
    <?php endif; ?>

    <div id="threadsWrap"><p class="text-faint">Loading…</p></div>
</div>

<?php if ($me['role'] === 'trader'): ?>
<div class="modal-overlay" id="newOfferModal">
    <div class="modal">
        <span class="modal-close" id="closeModal">×</span>
        <h3 class="mt-0">Propose an offer</h3>
        <div id="modalAlert"></div>
        <form id="newOfferForm">
            <div class="field">
                <label for="offerFarmer">Farmer</label>
                <select id="offerFarmer" required></select>
            </div>
            <div class="field">
                <label for="offerProduct">Commodity</label>
                <select id="offerProduct" required></select>
            </div>
            <div class="field">
                <label for="offerQuantity">Quantity</label>
                <input type="number" id="offerQuantity" min="0.1" step="0.1" required>
            </div>
            <div class="field">
                <label for="offerUnit">Unit</label>
                <input type="text" id="offerUnit" placeholder="kg, ton, bag…" required>
            </div>
            <div class="field">
                <label for="offerPrice">Price per unit ($)</label>
                <input type="number" id="offerPrice" min="0.01" step="0.01" required>
            </div>
            <div class="field">
                <label for="offerMessage">Message (optional)</label>
                <textarea id="offerMessage" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Send offer</button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
const myId = <?= (int)$me['id'] ?>;
const myRole = '<?= $me['role'] ?>';

function statusBadge(status) {
    return `<span class="badge badge-${status}">${status}</span>`;
}

function renderThread(thread) {
    const last = thread[thread.length - 1];
    const bubbles = thread.map(o => {
        const senderLabel = o.initiated_by === 'trader' ? o.trader_name + ' (trader)' : o.farmer_name + ' (farmer)';
        return `
        <div class="offer-bubble from-${o.initiated_by}">
            <div class="offer-meta">${escapeHtml(senderLabel)} · ${timeAgo(o.created_at)}</div>
            <div class="offer-price">$${Number(o.price).toFixed(2)} / ${escapeHtml(o.unit)} &nbsp;×&nbsp; ${o.quantity} ${escapeHtml(o.unit)}</div>
            ${o.message ? `<div class="text-dim" style="margin-top:4px; font-size:0.88rem;">"${escapeHtml(o.message)}"</div>` : ''}
        </div>`;
    }).join('');

    const canRespond = last.status === 'pending' &&
        ((myRole === 'trader' && myId === last.trader_id && last.initiated_by === 'farmer') ||
         (myRole === 'farmer' && myId === last.farmer_id && last.initiated_by === 'trader'));

    let actions = '';
    if (canRespond) {
        actions = `
        <div class="offer-actions">
            <button class="btn btn-primary btn-sm" onclick="respond(${last.id}, 'accept')">Accept deal</button>
            <button class="btn btn-danger btn-sm" onclick="respond(${last.id}, 'reject')">Reject</button>
            <button class="btn btn-outline btn-sm" onclick="toggleCounter(${last.id})">Counter-offer</button>
        </div>
        <div class="inline-form" id="counterForm-${last.id}" style="display:none;">
            <input type="number" step="0.01" min="0.01" placeholder="New price" id="counterPrice-${last.id}">
            <input type="number" step="0.1" min="0.1" placeholder="Quantity" id="counterQty-${last.id}" value="${last.quantity}">
            <input type="text" placeholder="Message (optional)" id="counterMsg-${last.id}">
            <button class="btn btn-primary btn-sm" onclick="sendCounter(${last.id})">Send counter</button>
        </div>`;
    } else if (last.status === 'pending') {
        actions = `<p class="text-faint" style="margin:10px 0 0; font-size:0.85rem;">Waiting for ${last.initiated_by === 'trader' ? last.farmer_name : last.trader_name} to respond.</p>`;
    }

    return `
    <div class="thread">
        <div class="thread-header">
            <div><strong>${escapeHtml(last.product_name)}</strong> <span class="text-faint">· ${escapeHtml(last.trader_name)} ↔ ${escapeHtml(last.farmer_name)}</span></div>
            ${statusBadge(last.status)}
        </div>
        <div class="thread-body">
            ${bubbles}
            ${actions}
        </div>
    </div>`;
}

function toggleCounter(offerId) {
    const el = document.getElementById('counterForm-' + offerId);
    el.style.display = el.style.display === 'none' ? 'flex' : 'none';
}

async function respond(offerId, response) {
    try {
        const data = await api('/api/offers.php?action=respond', { method: 'POST', body: { offer_id: offerId, response } });
        showToast(data.message, 'success');
        loadThreads();
    } catch (e) {}
}

async function sendCounter(offerId) {
    const price = document.getElementById('counterPrice-' + offerId).value;
    const quantity = document.getElementById('counterQty-' + offerId).value;
    const message = document.getElementById('counterMsg-' + offerId).value;
    if (!price) { showToast('Please enter a counter-offer price.', 'error'); return; }
    try {
        const data = await api('/api/offers.php?action=respond', {
            method: 'POST',
            body: { offer_id: offerId, response: 'counter', price, quantity, message }
        });
        showToast(data.message, 'success');
        loadThreads();
    } catch (e) {}
}

async function loadThreads() {
    try {
        const { threads } = await api('/api/offers.php');
        const wrap = document.getElementById('threadsWrap');
        wrap.innerHTML = threads.length
            ? threads.map(renderThread).join('')
            : `<div class="empty-state">No negotiations yet.${myRole === 'trader' ? ' Click "New offer to a farmer" to start one.' : ''}</div>`;
    } catch (e) {}
}
loadThreads();
setInterval(loadThreads, 20000);

<?php if ($me['role'] === 'trader'): ?>
const modal = document.getElementById('newOfferModal');
document.getElementById('newOfferBtn').addEventListener('click', async () => {
    modal.classList.add('open');
    try {
        const { farmers } = await api('/api/users.php?scope=farmers');
        document.getElementById('offerFarmer').innerHTML = farmers.map(f => `<option value="${f.id}">${escapeHtml(f.name)} (${escapeHtml(f.location || 'location n/a')})</option>`).join('');
        const { products } = await api('/api/products.php');
        document.getElementById('offerProduct').innerHTML = products.map(p => `<option value="${p.id}" data-unit="${escapeHtml(p.unit)}">${escapeHtml(p.name)}</option>`).join('');
        document.getElementById('offerUnit').value = products[0] ? products[0].unit : '';
    } catch (e) {}
});
document.getElementById('offerProduct').addEventListener('change', (e) => {
    document.getElementById('offerUnit').value = e.target.selectedOptions[0].dataset.unit || '';
});
document.getElementById('closeModal').addEventListener('click', () => modal.classList.remove('open'));
modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('open'); });

document.getElementById('newOfferForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const alertBox = document.getElementById('modalAlert');
    alertBox.innerHTML = '';
    try {
        await api('/api/offers.php', {
            method: 'POST',
            body: {
                farmer_id: document.getElementById('offerFarmer').value,
                product_id: document.getElementById('offerProduct').value,
                quantity: document.getElementById('offerQuantity').value,
                unit: document.getElementById('offerUnit').value,
                price: document.getElementById('offerPrice').value,
                message: document.getElementById('offerMessage').value
            }
        });
        showToast('Offer sent.', 'success');
        modal.classList.remove('open');
        document.getElementById('newOfferForm').reset();
        loadThreads();
    } catch (err) {
        alertBox.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
    }
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
