<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/staff_navbar.php'; ?>
<main class="flex-1 max-w-4xl mx-auto w-full px-6 py-8">
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-3xl text-stone-900"><?= e($pageTitle) ?></h1>
            <p class="text-stone-500 mt-1">Session code <span class="font-mono font-semibold"><?= e($session['session_code']) ?></span> &middot; <?= e($session['zone']) ?> &middot; started <?= e(date('d M H:i', strtotime($session['started_at']))) ?></p>
        </div>
        <div class="flex gap-2">
            <a href="/index.php?route=staff/billing&id=<?= (int)$session['id'] ?>" class="px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700">Billing</a>
            <?php if ($session['status'] === 'active' && !$hasBills): ?>
                <form method="POST" action="/index.php?route=staff/sessions/close" onsubmit="return confirm('Force-close this table session?');">
                    <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$session['id'] ?>">
                    <button type="submit" class="px-4 py-2 rounded-lg border border-red-300 text-red-600 text-sm font-semibold hover:bg-red-50">Force Close</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($msg = flash_get('success')): ?><div class="mt-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash_get('error')): ?><div class="mt-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>

    <?php if ($hasBills): ?>
        <p class="mt-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm px-3 py-2">
            The bill for this table has been generated, so its total is fixed and guests can no
            longer add to their order. Settle it under <strong>Billing</strong> — the session
            closes itself once every bill against it is paid.
        </p>
    <?php endif; ?>

    <section class="bg-white rounded-xl border border-amber-100 shadow-sm p-5 mt-6">
        <h2 class="font-semibold text-stone-800 mb-3">Guests (<?= count($members) ?>)</h2>
        <ul class="flex flex-wrap gap-2 text-sm">
            <?php foreach ($members as $m): ?>
                <li class="px-3 py-1 rounded-full bg-amber-50 border border-amber-100">
                    <?= e($m['display_name']) ?><?= $m['is_host'] ? ' <span class="text-amber-600">(host)</span>' : '' ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="mt-6 space-y-4">
        <?php foreach ($orders as $order): ?>
            <div class="bg-white rounded-xl border border-amber-100 shadow-sm p-5">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-semibold text-stone-800"><?= e($order['display_name']) ?>'s order</h3>
                    <div class="flex items-center gap-2">
                        <?= status_badge($order['status']) ?>
                        <?php if ($order['status'] === 'ready'): ?>
                            <form method="POST" action="/index.php?route=staff/orders/serve">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                <input type="hidden" name="session_id" value="<?= (int)$session['id'] ?>">
                                <button type="submit" class="text-xs px-2 py-1 rounded bg-green-600 text-white hover:bg-green-700">Mark Served</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <table class="w-full text-sm">
                    <tbody>
                    <?php foreach ($itemsByOrder[$order['id']] as $item): ?>
                        <tr class="border-t border-stone-100">
                            <td class="py-1.5"><?= veg_mark((bool)$item['is_veg']) ?> <?= (int)$item['quantity'] ?>&times; <?= e($item['name']) ?></td>
                            <td class="text-stone-400 text-xs"><?= e($item['notes'] ?? '') ?></td>
                            <td class="text-right"><?= status_badge($item['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?>
            <p class="text-center text-stone-400 py-6">No orders placed yet.</p>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
