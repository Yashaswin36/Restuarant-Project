<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/staff_navbar.php'; ?>
<main class="flex-1 max-w-6xl mx-auto w-full px-6 py-8">
    <h1 class="text-3xl text-stone-900"><?= e($pageTitle) ?></h1>
    <p class="text-stone-500 mt-1 mb-6">System-wide overview across the restaurant.</p>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <?php foreach ([
            ['Tables', $stats['tables']],
            ['Staff', $stats['staff']],
            ['Kitchen', $stats['kitchen']],
            ['Pending Bookings', $stats['pending_res']],
            ['Confirmed Today+', $stats['confirmed_res']],
            ['Active Table Sessions', $stats['active_sessions']],
        ] as [$label, $value]): ?>
            <div class="bg-white rounded-xl border border-amber-100 p-4 text-center shadow-sm">
                <div class="text-2xl font-bold text-amber-700"><?= (int)$value ?></div>
                <div class="text-xs text-stone-500 mt-1 uppercase tracking-wide"><?= e($label) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-amber-100 p-5 shadow-sm">
            <div class="text-2xl font-bold text-green-700"><?= money($revenue['total_revenue']) ?></div>
            <div class="text-xs text-stone-500 mt-1 uppercase tracking-wide">Total Revenue (Paid Bills)</div>
        </div>
        <div class="bg-white rounded-xl border border-amber-100 p-5 shadow-sm">
            <div class="text-2xl font-bold text-red-600"><?= money($revenue['unpaid_amount']) ?></div>
            <div class="text-xs text-stone-500 mt-1 uppercase tracking-wide">Unpaid Bills</div>
        </div>
    </div>

    <section class="bg-white rounded-xl border border-amber-100 shadow-sm p-5">
        <h2 class="font-semibold text-stone-800 mb-3">Today's Reservation Queue</h2>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-stone-500 border-b border-stone-200">
                <th class="py-2">Time</th><th>Customer</th><th>Party</th><th>Table</th><th>Status</th>
            </tr></thead>
            <tbody>
            <?php if (empty($todayQueue)): ?>
                <tr><td colspan="5" class="py-4 text-center text-stone-400">No reservations today.</td></tr>
            <?php endif; ?>
            <?php foreach ($todayQueue as $r): ?>
                <tr class="border-b border-stone-100">
                    <td class="py-2"><?= e(substr($r['reservation_time'], 0, 5)) ?></td>
                    <td><?= e($r['customer_name']) ?></td>
                    <td><?= (int)$r['party_size'] ?></td>
                    <td><?= e($r['table_number'] ?? '—') ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
