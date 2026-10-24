<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/staff_navbar.php'; ?>
<main class="flex-1 max-w-6xl mx-auto w-full px-6 py-8">
    <h1 class="text-3xl text-stone-900"><?= e($pageTitle) ?></h1>
    <p class="text-stone-500 mt-1 mb-6">Read-only oversight — staff manage the day-to-day queue.</p>

    <section class="bg-white rounded-xl border border-amber-100 shadow-sm p-5">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-stone-500 border-b border-stone-200">
                <th class="py-2">Date</th><th>Time</th><th>Customer</th><th>Party</th><th>Table</th><th>Status</th><th>Notes</th>
            </tr></thead>
            <tbody>
            <?php foreach ($reservations as $r): ?>
                <tr class="border-b border-stone-100">
                    <td class="py-2"><?= e($r['reservation_date']) ?></td>
                    <td><?= e(substr($r['reservation_time'], 0, 5)) ?></td>
                    <td><?= e($r['customer_name']) ?> <span class="text-stone-400"><?= e($r['customer_email']) ?></span></td>
                    <td><?= (int)$r['party_size'] ?></td>
                    <td><?= e($r['table_number'] ?? '—') ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td class="text-stone-500"><?= e($r['special_requests'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
