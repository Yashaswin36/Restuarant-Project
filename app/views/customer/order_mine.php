<?php require __DIR__ . '/../partials/header.php'; ?>
<header class="bg-white border-b border-amber-100 px-6 py-4 flex items-center justify-between">
    <?= wordmark() ?>
    <?php if (empty($orderingClosed)): ?>
        <a href="/index.php?route=order/menu" class="text-sm text-amber-700 hover:underline">&larr; Back to Menu</a>
    <?php else: ?>
        <span class="text-sm text-stone-400">Kitchen closed for this table</span>
    <?php endif; ?>
</header>
<main class="flex-1 max-w-2xl mx-auto w-full px-4 py-8">
    <h1 class="text-3xl text-stone-900"><?= e($pageTitle) ?></h1>
    <p class="text-stone-500 mt-1 mb-6">Ordering as <?= e(CustomerSession::memberName()) ?></p>

    <?php if ($msg = flash_get('success')): ?><div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash_get('error')): ?><div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>

    <?php if (!empty($orderingClosed)): ?>
        <div class="mb-6 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm px-4 py-3">
            Your table's bill has been generated, so the total below is final and no further
            orders can be added. Please speak to your server if you'd like anything else.
        </div>
    <?php endif; ?>

    <?php if (!empty($myBills)): ?>
        <?php foreach ($myBills as $bill): ?>
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-6">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="font-semibold text-stone-800"><?= $bill['bill_type'] === 'combined' ? 'Table Bill' : 'Your Bill' ?></h2>
                    <?= status_badge($bill['status']) ?>
                </div>
                <div class="flex justify-between text-sm text-stone-600"><span>Subtotal</span><span><?= money($bill['subtotal']) ?></span></div>
                <div class="flex justify-between text-sm text-stone-600"><span>Tax</span><span><?= money($bill['tax_amount']) ?></span></div>
                <div class="flex justify-between font-bold text-stone-800 mt-1 pt-1 border-t border-amber-200"><span>Total</span><span><?= money($bill['total_amount']) ?></span></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="space-y-4">
        <?php if (empty($orders)): ?>
            <p class="text-center text-stone-400 py-10">No orders yet — head back to the menu!</p>
        <?php endif; ?>
        <?php foreach ($orders as $order): ?>
            <div class="bg-white rounded-xl border border-amber-100 shadow-sm p-5">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-stone-400"><?= e(date('H:i', strtotime($order['placed_at']))) ?></span>
                    <?= status_badge($order['status']) ?>
                </div>
                <ul class="text-sm divide-y divide-stone-100">
                    <?php foreach ($itemsByOrder[$order['id']] as $item): ?>
                        <li class="py-1.5 flex justify-between">
                            <span><?= veg_mark((bool)$item['is_veg']) ?> <?= (int)$item['quantity'] ?>&times; <?= e($item['name']) ?></span>
                            <span class="text-xs text-stone-400 capitalize"><?= e($item['status']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
