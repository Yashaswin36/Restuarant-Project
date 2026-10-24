<?php require __DIR__ . '/../partials/header.php'; ?>
<header class="bg-white border-b border-amber-100 px-6 py-4 flex items-center justify-between sticky top-0 z-10">
    <div>
        <?= wordmark() ?>
        <div class="text-xs text-stone-400 mt-0.5">Ordering as <?= e(CustomerSession::memberName()) ?></div>
    </div>
    <a href="/index.php?route=order/mine" class="text-sm text-amber-700 hover:underline">My Orders &rarr;</a>
</header>

<main class="flex-1 max-w-5xl mx-auto w-full px-4 py-6">
    <?php if ($msg = flash_get('error')): ?><div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash_get('success')): ?><div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>

    <div class="flex flex-wrap items-center gap-2 mb-6">
        <button class="category-filter-btn active bg-amber-600 text-white px-3 py-1.5 rounded-full text-sm font-medium" data-category="all">All</button>
        <?php foreach ($menu as $catId => $cat): ?>
            <button class="category-filter-btn bg-white text-stone-600 border border-stone-200 px-3 py-1.5 rounded-full text-sm font-medium" data-category="<?= (int)$catId ?>"><?= e($cat['category_name']) ?></button>
        <?php endforeach; ?>
        <label class="ml-auto flex items-center gap-2 text-sm text-stone-600 cursor-pointer">
            <input type="checkbox" id="veg-only-toggle" class="accent-green-600"> <?= veg_mark(true) ?> Veg only
        </label>
    </div>

    <?php foreach ($menu as $catId => $cat): ?>
        <section class="menu-category-section mb-8" data-category="<?= (int)$catId ?>">
            <h2 class="text-red-700 text-xl mb-3 flex items-center gap-3">
                <?= e($cat['category_name']) ?>
                <span class="h-px flex-1 bg-amber-200/80"></span>
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php foreach ($cat['items'] as $item): ?>
                    <div class="menu-item-card bg-white rounded-xl border border-amber-100 shadow-sm p-4 flex justify-between items-start gap-3" data-veg="<?= (int)$item['is_veg'] ?>">
                        <div class="min-w-0">
                            <div class="font-semibold text-stone-900 flex items-baseline gap-1.5"><?= veg_mark((bool)$item['is_veg']) ?> <?= e($item['name']) ?></div>
                            <?php if ($item['description']): ?><div class="text-xs text-stone-400 mt-1 leading-relaxed"><?= e($item['description']) ?></div><?php endif; ?>
                            <div class="text-sm text-amber-700 font-semibold mt-1.5 tnum"><?= money($item['price']) ?></div>
                        </div>
                        <button data-id="<?= (int)$item['id'] ?>" class="add-to-cart-btn shrink-0 px-3 py-1.5 rounded-lg bg-amber-100 text-amber-700 text-sm font-semibold hover:bg-amber-200">+ Add</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</main>

<button id="cart-toggle" aria-label="Open your cart"
        class="fixed bottom-6 right-6 w-16 h-16 rounded-full bg-amber-600 text-white shadow-lg flex items-center justify-center hover:bg-amber-700 transition-colors">
    <svg viewBox="0 0 24 24" class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M3 4h2.2l2.1 10.4a1.5 1.5 0 0 0 1.5 1.2h7.9a1.5 1.5 0 0 0 1.5-1.2L20 7H6"/>
        <circle cx="9.5" cy="19.5" r="1.3"/><circle cx="17" cy="19.5" r="1.3"/>
    </svg>
    <span id="cart-badge" class="hidden absolute -top-1 -right-1 bg-red-600 text-white text-xs font-semibold w-6 h-6 rounded-full flex items-center justify-center tnum">0</span>
</button>

<div id="cart-drawer" class="fixed top-0 right-0 h-full w-full sm:w-96 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 z-20 flex flex-col">
    <div class="flex justify-between items-center px-5 py-4 border-b border-amber-100">
        <h2 class="font-bold text-stone-800">Your Cart</h2>
        <button id="cart-close" class="text-stone-400 hover:text-stone-600 text-xl">&times;</button>
    </div>
    <div id="cart-drawer-lines" class="flex-1 overflow-y-auto px-5"></div>
    <div class="px-5 py-4 border-t border-amber-100">
        <div class="flex justify-between font-bold text-stone-800 mb-3">
            <span>Total</span><span id="cart-drawer-total">₹0.00</span>
        </div>
        <form id="place-order-form" method="POST" action="/index.php?route=order/place">
            <?= Csrf::field() ?>
            <button type="submit" class="w-full py-3 rounded-xl bg-amber-600 text-white font-semibold hover:bg-amber-700">Place Order</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
<script>
window.CART_DATA = {
    csrfToken: <?= json_encode(Csrf::token()) ?>,
    cartDataUrl: '/index.php?route=order/cart',
    cartAddUrl: '/index.php?route=order/cart/add',
    cartUpdateUrl: '/index.php?route=order/cart/update',
    ordersUrl: '/index.php?route=order/mine'
};
</script>
<script src="/assets/js/cart.js"></script>
