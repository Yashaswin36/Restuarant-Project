<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/staff_navbar.php'; ?>
<main class="flex-1 max-w-6xl mx-auto w-full px-6 py-8">
    <h1 class="text-3xl text-stone-900"><?= e($pageTitle) ?></h1>
    <p class="text-stone-500 mt-1 mb-6">Welcome back, <?= e(Auth::name()) ?>.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <section class="bg-white rounded-xl border border-amber-100 shadow-sm p-5">
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-semibold text-stone-800">Today's Reservations</h2>
                <a href="/index.php?route=staff/reservations" class="text-sm text-amber-700 hover:underline">Manage &rarr;</a>
            </div>
            <ul class="divide-y divide-stone-100 text-sm">
                <?php if (empty($todayQueue)): ?><li class="py-3 text-stone-400">No reservations today.</li><?php endif; ?>
                <?php foreach ($todayQueue as $r): ?>
                    <li class="py-2 flex justify-between">
                        <span><?= e(substr($r['reservation_time'], 0, 5)) ?> — <?= e($r['customer_name']) ?> (<?= (int)$r['party_size'] ?>)</span>
                        <?= status_badge($r['status']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-amber-100 shadow-sm p-5">
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-semibold text-stone-800">Active Tables</h2>
                <a href="/index.php?route=staff/tables" class="text-sm text-amber-700 hover:underline">View all &rarr;</a>
            </div>
            <ul class="divide-y divide-stone-100 text-sm">
                <?php if (empty($activeSessions)): ?><li class="py-3 text-stone-400">No tables currently in a session.</li><?php endif; ?>
                <?php foreach ($activeSessions as $s): ?>
                    <li class="py-2 flex justify-between">
                        <span>Table <?= e($s['table_number']) ?> &middot; <?= (int)$s['member_count'] ?> guest(s)</span>
                        <a href="/index.php?route=staff/sessions/detail&id=<?= (int)$s['id'] ?>" class="text-amber-700 hover:underline">View</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
