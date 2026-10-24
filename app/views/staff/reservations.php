<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/staff_navbar.php'; ?>
<main class="flex-1 max-w-6xl mx-auto w-full px-6 py-8">
    <h1 class="text-3xl text-stone-900"><?= e($pageTitle) ?></h1>

    <?php if ($msg = flash_get('success')): ?><div class="mt-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>

    <section class="bg-white rounded-xl border border-amber-100 shadow-sm p-5 mt-6">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-stone-500 border-b border-stone-200">
                <th class="py-2">Date</th><th>Time</th><th>Customer</th><th>Party</th><th>Status</th><th>Notes</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($reservations as $r): ?>
                <tr class="border-b border-stone-100 align-top">
                    <td class="py-2"><?= e($r['reservation_date']) ?></td>
                    <td><?= e(substr($r['reservation_time'], 0, 5)) ?></td>
                    <td><?= e($r['customer_name']) ?><br><span class="text-stone-400 text-xs"><?= e($r['customer_email']) ?></span></td>
                    <td><?= (int)$r['party_size'] ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td class="text-stone-500 text-xs max-w-[160px]"><?= e($r['special_requests'] ?? '') ?></td>
                    <td class="py-2">
                        <?php if ($r['status'] === 'pending'): ?>
                            <form method="POST" action="/index.php?route=staff/reservations/confirm" class="inline">
                                <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="text-green-700 hover:underline text-sm">Confirm</button>
                            </form>
                            <form method="POST" action="/index.php?route=staff/reservations/cancel" class="inline ml-2">
                                <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="text-red-600 hover:underline text-sm">Cancel</button>
                            </form>
                        <?php elseif ($r['status'] === 'confirmed'): ?>
                            <form method="POST" action="/index.php?route=staff/reservations/seat" class="flex gap-2 items-center">
                                <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <select name="table_id" required class="text-xs rounded border border-stone-300 px-1 py-1">
                                    <option value="">Table&hellip;</option>
                                    <?php foreach ($tables as $t): ?>
                                        <option value="<?= (int)$t['id'] ?>"><?= e($t['table_number']) ?> (<?= (int)$t['capacity'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="text-amber-700 hover:underline text-sm">Seat</button>
                            </form>
                            <form method="POST" action="/index.php?route=staff/reservations/cancel" class="inline mt-1">
                                <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="text-red-600 hover:underline text-sm">Cancel</button>
                            </form>
                        <?php else: ?>
                            <span class="text-stone-400 text-xs">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
