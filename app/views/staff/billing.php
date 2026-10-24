<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/staff_navbar.php'; ?>
<main class="flex-1 max-w-2xl mx-auto w-full px-6 py-8">
    <h1 class="text-3xl text-stone-900"><?= e($pageTitle) ?></h1>
    <p class="text-stone-500 mt-1 mb-6"><a href="/index.php?route=staff/sessions/detail&id=<?= (int)$session['id'] ?>" class="hover:underline">&larr; back to table</a></p>

    <?php if ($msg = flash_get('success')): ?><div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash_get('error')): ?><div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>

    <?php if (empty($bills)): ?>
        <section class="bg-white rounded-xl border border-amber-100 shadow-sm p-6">
            <h2 class="font-semibold text-stone-800 mb-1">Generate Bill</h2>
            <p class="text-sm text-stone-500 mb-4">Choose how to split the bill for this table's <?= count($members) ?> guest(s).</p>
            <form method="POST" action="/index.php?route=staff/billing/generate" class="flex gap-3">
                <?= Csrf::field() ?>
                <input type="hidden" name="session_id" value="<?= (int)$session['id'] ?>">
                <button type="submit" name="mode" value="combined" class="flex-1 py-3 rounded-lg bg-amber-600 text-white font-semibold hover:bg-amber-700">Combined Bill</button>
                <button type="submit" name="mode" value="split" class="flex-1 py-3 rounded-lg border border-amber-300 text-amber-700 font-semibold hover:bg-amber-50">Split by Guest</button>
            </form>
        </section>
    <?php else: ?>
        <section class="space-y-4">
            <?php foreach ($bills as $bill): ?>
                <div class="bg-white rounded-xl border border-amber-100 shadow-sm p-5">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h3 class="font-semibold text-stone-800">
                                <?= $bill['bill_type'] === 'combined' ? 'Combined Bill — whole table' : 'Bill — ' . e($bill['display_name']) ?>
                            </h3>
                            <p class="text-xs text-stone-500">Generated <?= e(date('d M H:i', strtotime($bill['generated_at']))) ?></p>
                        </div>
                        <?= status_badge($bill['status']) ?>
                    </div>
                    <table class="w-full text-sm mt-3">
                        <tr><td class="py-0.5 text-stone-500">Subtotal</td><td class="text-right"><?= money($bill['subtotal']) ?></td></tr>
                        <tr><td class="py-0.5 text-stone-500">Tax (5%)</td><td class="text-right"><?= money($bill['tax_amount']) ?></td></tr>
                        <tr class="font-bold border-t border-stone-200"><td class="py-1">Total</td><td class="text-right"><?= money($bill['total_amount']) ?></td></tr>
                    </table>
                    <?php if ($bill['status'] === 'unpaid'): ?>
                        <form method="POST" action="/index.php?route=staff/billing/markpaid" class="mt-3">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="bill_id" value="<?= (int)$bill['id'] ?>">
                            <input type="hidden" name="session_id" value="<?= (int)$session['id'] ?>">
                            <button type="submit" class="w-full py-2 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700">Mark Paid (Cash)</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
