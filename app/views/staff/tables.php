<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/staff_navbar.php'; ?>
<main class="flex-1 max-w-6xl mx-auto w-full px-6 py-8">
    <h1 class="text-3xl text-stone-900"><?= e($pageTitle) ?></h1>

    <?php if ($msg = flash_get('success')): ?><div class="mt-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash_get('error')): ?><div class="mt-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>

    <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mt-6">
        <?php foreach ($tables as $t): ?>
            <?php $session = $sessionsByTable[$t['id']] ?? null; ?>
            <div class="bg-white rounded-xl border <?= $session ? 'border-amber-300' : 'border-amber-100' ?> shadow-sm p-4 text-center">
                <div class="font-bold text-lg text-stone-800">Table <?= e($t['table_number']) ?></div>
                <div class="text-xs text-stone-500 mb-2"><?= (int)$t['capacity'] ?> seats &middot; <?= e($t['zone']) ?></div>
                <?php if ($session): ?>
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-700 mb-2">In session &middot; <?= (int)$session['member_count'] ?> guest(s)</span>
                    <br>
                    <a href="/index.php?route=staff/sessions/detail&id=<?= (int)$session['id'] ?>" class="text-sm text-amber-700 hover:underline font-medium">Open &rarr;</a>
                <?php else: ?>
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs bg-stone-100 text-stone-500">Available</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
