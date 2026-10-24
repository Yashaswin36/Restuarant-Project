<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/customer_topbar.php'; ?>
<main class="flex-1 max-w-2xl mx-auto w-full px-4 py-10">
    <h1 class="text-3xl text-stone-900"><?= e($pageTitle) ?></h1>
    <p class="text-stone-500 mt-1 mb-6">Signed in as <?= e($_SESSION['booking_customer_email'] ?? '') ?></p>

    <?php if ($msg = flash_get('success')): ?><div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>

    <div class="space-y-4">
        <?php if (empty($reservations)): ?>
            <p class="text-center text-stone-400 py-10">No bookings yet.</p>
        <?php endif; ?>
        <?php foreach ($reservations as $r): ?>
            <div class="bg-white rounded-xl border border-amber-100 shadow-sm p-5 flex justify-between items-center">
                <div>
                    <div class="font-semibold text-stone-800"><?= e(date('d M Y', strtotime($r['reservation_date']))) ?> at <?= e(substr($r['reservation_time'], 0, 5)) ?></div>
                    <div class="text-sm text-stone-500">Party of <?= (int)$r['party_size'] ?><?= $r['table_number'] ? ' &middot; Table ' . e($r['table_number']) : '' ?></div>
                    <?php if ($r['special_requests']): ?><div class="text-xs text-stone-400 mt-1"><?= e($r['special_requests']) ?></div><?php endif; ?>
                </div>
                <div class="text-right">
                    <?= status_badge($r['status']) ?>
                    <?php if (in_array($r['status'], ['pending', 'confirmed'], true)): ?>
                        <form method="POST" action="/index.php?route=booking/cancel" class="mt-2" onsubmit="return confirm('Cancel this booking?');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button type="submit" class="text-xs text-red-600 hover:underline">Cancel</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="mt-8 text-center"><a href="/index.php?route=booking/create" class="text-amber-700 hover:underline text-sm">+ Book another table</a></p>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
