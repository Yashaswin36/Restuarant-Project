(function () {
    'use strict';

    var data = window.CART_DATA;
    if (!data) return;

    var badge = document.getElementById('cart-badge');
    var drawer = document.getElementById('cart-drawer');
    var drawerLines = document.getElementById('cart-drawer-lines');
    var drawerTotal = document.getElementById('cart-drawer-total');
    var placeForm = document.getElementById('place-order-form');

    function post(url, params) {
        var body = new URLSearchParams(params);
        body.set('csrf_token', data.csrfToken);
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(function (r) { return r.json(); });
    }

    function money(n) {
        return '₹' + n.toFixed(2);
    }

    // Mirrors veg_mark() in core/helpers.php — the square-outline FSSAI food mark, so a
    // dish looks identical whether the server rendered it or the cart drawer did.
    function fmark(isVeg) {
        var veg = isVeg == 1;
        return '<span class="fmark ' + (veg ? 'fmark-veg' : 'fmark-nonveg') + '" role="img" aria-label="' +
            (veg ? 'Vegetarian' : 'Non-vegetarian') + '"></span>';
    }

    function refreshCart() {
        fetch(data.cartDataUrl).then(function (r) { return r.json(); }).then(function (cart) {
            badge.textContent = cart.count;
            badge.classList.toggle('hidden', cart.count === 0);

            if (cart.lines.length === 0) {
                drawerLines.innerHTML = '<p class="text-center text-stone-400 py-8">Your cart is empty.</p>';
                placeForm.classList.add('hidden');
            } else {
                drawerLines.innerHTML = cart.lines.map(function (line) {
                    return `<div class="flex items-center justify-between py-3 border-b border-stone-100">
                        <div>
                            <div class="font-medium text-stone-800">${fmark(line.is_veg)} ${line.name}</div>
                            <div class="text-xs text-stone-400">${money(line.price)} each</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button data-id="${line.menu_item_id}" data-delta="-1" class="qty-btn w-7 h-7 rounded-full border border-stone-300 text-stone-600 hover:bg-stone-100">&minus;</button>
                            <span class="w-6 text-center">${line.quantity}</span>
                            <button data-id="${line.menu_item_id}" data-delta="1" class="qty-btn w-7 h-7 rounded-full border border-stone-300 text-stone-600 hover:bg-stone-100">+</button>
                        </div>
                    </div>`;
                }).join('');
                placeForm.classList.remove('hidden');

                drawerLines.querySelectorAll('.qty-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var id = btn.getAttribute('data-id');
                        var delta = parseInt(btn.getAttribute('data-delta'), 10);
                        var line = cart.lines.find(function (l) { return String(l.menu_item_id) === id; });
                        var newQty = Math.max(0, line.quantity + delta);
                        post(data.cartUpdateUrl, { menu_item_id: id, quantity: newQty }).then(refreshCart);
                    });
                });
            }

            drawerTotal.textContent = money(cart.total);
        });
    }

    document.querySelectorAll('.add-to-cart-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-id');
            btn.disabled = true;
            post(data.cartAddUrl, { menu_item_id: id }).then(function (res) {
                // Staff generated the bill while this page was open — the server refuses new
                // items from here on, so reload into the "kitchen closed" screen rather than
                // letting the guest keep filling a cart that can never be placed.
                if (res && res.closed) {
                    window.location.href = data.ordersUrl;
                    return;
                }
                btn.disabled = false;
                refreshCart();
                btn.textContent = 'Added ✓';
                setTimeout(function () { btn.textContent = '+ Add'; }, 800);
            });
        });
    });

    document.getElementById('cart-toggle').addEventListener('click', function () {
        drawer.classList.toggle('translate-x-full');
    });
    document.getElementById('cart-close').addEventListener('click', function () {
        drawer.classList.add('translate-x-full');
    });

    // Category filter + veg-only toggle
    var vegOnly = document.getElementById('veg-only-toggle');
    var catButtons = document.querySelectorAll('.category-filter-btn');
    var sections = document.querySelectorAll('.menu-category-section');

    function applyFilters() {
        var activeCat = document.querySelector('.category-filter-btn.active').getAttribute('data-category');
        sections.forEach(function (section) {
            var matchCat = activeCat === 'all' || section.getAttribute('data-category') === activeCat;
            section.classList.toggle('hidden', !matchCat);
            section.querySelectorAll('.menu-item-card').forEach(function (card) {
                var vegOk = !vegOnly.checked || card.getAttribute('data-veg') === '1';
                card.classList.toggle('hidden', !vegOk);
            });
        });
    }

    catButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            catButtons.forEach(function (b) { b.classList.remove('active', 'bg-amber-600', 'text-white'); b.classList.add('bg-white', 'text-stone-600'); });
            btn.classList.add('active', 'bg-amber-600', 'text-white');
            btn.classList.remove('bg-white', 'text-stone-600');
            applyFilters();
        });
    });
    vegOnly.addEventListener('change', applyFilters);

    refreshCart();
})();
