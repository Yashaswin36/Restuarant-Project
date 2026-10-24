<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/staff_navbar.php'; ?>
<main class="flex-1 max-w-7xl mx-auto w-full px-6 py-8">
    <div class="flex flex-wrap justify-between items-end gap-3 mb-6">
        <div>
            <p class="eyebrow">Expo board</p>
            <h1 class="text-3xl text-stone-900 mt-1"><?= e($pageTitle) ?></h1>
            <p class="text-stone-500 mt-1.5">Grouped by station, not by table — each column is one section of the pass.</p>
        </div>
        <span id="last-refresh" class="text-xs text-stone-400 tnum flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
        </span>
    </div>

    <div id="board" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 items-start"></div>

    <section class="mt-10 pt-6 border-t border-amber-200/70">
        <h2 class="text-sm font-semibold text-stone-700 mb-3">Passed to the floor</h2>
        <ul id="recently-ready" class="flex flex-wrap gap-2 text-sm"></ul>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
<script>
const CSRF_TOKEN = <?= json_encode(Csrf::token()) ?>;
const INITIAL_DATA = <?= json_encode(['categories' => array_values($categories), 'recentlyReady' => $recentlyReady]) ?>;
const DATA_URL = '/index.php?route=kitchen/queue/data';
const ADVANCE_URL = '/index.php?route=kitchen/item/advance';

function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    return String(value).replace(/[&<>"']/g, function (ch) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
    });
}

function vegDot(isVeg) {
    var veg = isVeg == 1;
    return '<span class="fmark ' + (veg ? 'fmark-veg' : 'fmark-nonveg') + '" role="img" aria-label="' +
        (veg ? 'Vegetarian' : 'Non-vegetarian') + '"></span>';
}

function timeAgo(dateStr) {
    const seconds = Math.floor((Date.now() - new Date(dateStr.replace(' ', 'T')).getTime()) / 1000);
    if (seconds < 60) return seconds + 's ago';
    const mins = Math.floor(seconds / 60);
    if (mins < 60) return mins + 'm ago';
    const hours = Math.floor(mins / 60);
    if (hours < 24) return hours + 'h ' + (mins % 60) + 'm ago';
    return Math.floor(hours / 24) + 'd ago';
}

function actionLabel(status) {
    return status === 'placed' ? 'Start Preparing' : 'Mark Ready';
}

function renderBoard(data) {
    const board = document.getElementById('board');
    board.innerHTML = '';

    if (data.categories.length === 0) {
        board.innerHTML = '<p class="col-span-full text-center text-stone-400 py-10">No active orders right now.</p>';
    }

    // Each station gets its own spine colour so a cook can find their column without
    // reading the headings — the same way sections of a real pass are colour-tagged.
    const SPINES = ['border-l-red-600', 'border-l-amber-500', 'border-l-green-600', 'border-l-stone-500', 'border-l-red-400'];

    data.categories.forEach(function (col, index) {
        const column = document.createElement('div');
        column.className = 'bg-white rounded-xl border border-amber-100 border-l-4 ' +
            SPINES[index % SPINES.length] + ' shadow-sm overflow-hidden';

        let itemsHtml = col.items.map(function (item) {
            const waiting = item.status === 'placed';
            return `<li class="px-4 py-3 border-t border-stone-100 first:border-t-0">
                <div class="flex justify-between items-start gap-3">
                    <div class="min-w-0">
                        <div class="font-semibold text-stone-900 leading-snug">
                            ${vegDot(item.is_veg)} <span class="tnum text-amber-700">${item.quantity}&times;</span> ${escapeHtml(item.name)}
                        </div>
                        <div class="text-xs text-stone-400 mt-0.5 tnum">
                            Table ${escapeHtml(item.table_number)} &middot; ${timeAgo(item.updated_at)}
                        </div>
                        ${item.notes ? `<div class="text-xs text-red-700 bg-red-50 border border-red-100 rounded px-1.5 py-0.5 mt-1 inline-block">${escapeHtml(item.notes)}</div>` : ''}
                    </div>
                    <button data-item-id="${item.id}" class="advance-btn shrink-0 text-xs px-2.5 py-1.5 rounded-lg font-semibold transition-colors ${waiting ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-green-600 text-white hover:bg-green-700'}">
                        ${actionLabel(item.status)}
                    </button>
                </div>
            </li>`;
        }).join('');

        column.innerHTML = `<div class="px-4 pt-4 pb-2 flex items-baseline justify-between gap-2">
                <h3 class="font-display font-bold text-stone-900">${escapeHtml(col.category_name)}</h3>
                <span class="text-xs text-stone-400 tnum">${col.items.length}</span>
            </div>
            <ul>${itemsHtml}</ul>`;
        board.appendChild(column);
    });

    board.querySelectorAll('.advance-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.disabled = true;
            const body = new URLSearchParams();
            body.set('csrf_token', CSRF_TOKEN);
            body.set('item_id', btn.getAttribute('data-item-id'));
            fetch(ADVANCE_URL, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() })
                .then(function () { refresh(); });
        });
    });

    const recentEl = document.getElementById('recently-ready');
    recentEl.innerHTML = data.recentlyReady.map(function (item) {
        return `<li class="px-3 py-1 rounded-lg bg-green-50 border border-green-100 text-green-800 tnum">
            ${item.quantity}&times; ${escapeHtml(item.name)} <span class="text-green-600/70">&middot; Table ${escapeHtml(item.table_number)}</span>
        </li>`;
    }).join('') || '<li class="text-stone-400">Nothing has gone out yet this service.</li>';

    const stamp = document.getElementById('last-refresh');
    stamp.textContent = '';
    stamp.insertAdjacentHTML('beforeend',
        '<span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span> Live &middot; ' +
        new Date().toLocaleTimeString());
}

function refresh() {
    fetch(DATA_URL).then(function (r) { return r.json(); }).then(renderBoard).catch(function () {});
}

renderBoard(INITIAL_DATA);
setInterval(refresh, 5000);
</script>
