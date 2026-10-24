<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/customer_topbar.php'; ?>
<main class="flex-1 flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-sm text-center">
        <div class="text-3xl mb-2">🍽️</div>
        <h1 class="text-xl font-bold text-stone-800">Table <?= e($table['table_number']) ?></h1>
        <p class="text-stone-500 mt-1 mb-8"><?= e($table['zone']) ?> &middot; seats <?= (int)$table['capacity'] ?></p>

        <?php if ($msg = flash_get('error')): ?><div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>

        <div class="space-y-3">
            <?php if ($activeSession): ?>
                <a href="/index.php?route=table/joincode&code=<?= e($table['table_code']) ?>" class="block py-3 rounded-xl bg-amber-600 text-white font-semibold hover:bg-amber-700">Join with a Code</a>
                <p class="text-xs text-stone-400">Someone at your table already started ordering — ask them for the join code.</p>
            <?php else: ?>
                <a href="/index.php?route=table/start&code=<?= e($table['table_code']) ?>" class="block py-3 rounded-xl bg-amber-600 text-white font-semibold hover:bg-amber-700">Start Ordering (I'm first)</a>
                <a href="/index.php?route=table/joincode&code=<?= e($table['table_code']) ?>" class="block py-3 rounded-xl border border-amber-300 text-amber-700 font-semibold hover:bg-amber-50">I Have a Join Code</a>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
