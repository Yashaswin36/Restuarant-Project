<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/staff_navbar.php'; ?>
<main class="flex-1 max-w-6xl mx-auto w-full px-6 py-8">
    <h1 class="text-3xl text-stone-900"><?= e($pageTitle) ?></h1>

    <?php if ($msg = flash_get('success')): ?><div class="mt-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash_get('error')): ?><div class="mt-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>

    <section class="bg-white rounded-xl border border-amber-100 shadow-sm p-5 mt-6">
        <h2 class="font-semibold text-stone-800 mb-3"><?= $editing ? 'Edit Table' : 'Add Table' ?></h2>
        <form method="POST" action="/index.php?route=admin/tables/<?= $editing ? 'update' : 'store' ?>" class="flex flex-wrap gap-3 items-end">
            <?= Csrf::field() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>
            <div>
                <label class="block text-xs text-stone-500 mb-1">Table Number</label>
                <input type="text" name="table_number" value="<?= e($editing['table_number'] ?? '') ?>" required
                       class="rounded-lg border border-stone-300 px-3 py-2 w-28 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
            <div>
                <label class="block text-xs text-stone-500 mb-1">Capacity</label>
                <input type="number" name="capacity" min="1" value="<?= e((string)($editing['capacity'] ?? '2')) ?>" required
                       class="rounded-lg border border-stone-300 px-3 py-2 w-24 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
            <div>
                <label class="block text-xs text-stone-500 mb-1">Zone</label>
                <input type="text" name="zone" value="<?= e($editing['zone'] ?? '') ?>" required placeholder="Indoor / Patio / Balcony"
                       class="rounded-lg border border-stone-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
            <button type="submit" class="px-5 py-2 rounded-lg bg-amber-600 text-white font-semibold hover:bg-amber-700"><?= $editing ? 'Save' : 'Add Table' ?></button>
            <?php if ($editing): ?><a href="/index.php?route=admin/tables" class="px-4 py-2 text-stone-500 hover:text-stone-700">Cancel</a><?php endif; ?>
        </form>
    </section>

    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
        <?php foreach ($tables as $table): ?>
            <div class="bg-white rounded-xl border border-amber-100 shadow-sm p-5">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <div class="font-bold text-lg text-stone-800">Table <?= e($table['table_number']) ?></div>
                        <div class="text-sm text-stone-500"><?= (int)$table['capacity'] ?> seats &middot; <?= e($table['zone']) ?></div>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs <?= $table['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-stone-100 text-stone-500' ?>"><?= e($table['status']) ?></span>
                </div>
                <img src="<?= e($table['qr_data_uri']) ?>" alt="QR code for table <?= e($table['table_number']) ?>" class="w-32 h-32 mx-auto border border-stone-100 rounded-lg">
                <div class="flex justify-center gap-3 mt-3 text-sm">
                    <a href="/index.php?route=admin/tables/qr&id=<?= (int)$table['id'] ?>" class="text-amber-700 hover:underline">Download QR</a>
                    <a href="/index.php?route=admin/tables&edit=<?= (int)$table['id'] ?>" class="text-stone-500 hover:underline">Edit</a>
                    <?php if (!$table['in_session']): ?>
                        <form method="POST" action="/index.php?route=admin/tables/status" class="inline">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$table['id'] ?>">
                            <button type="submit" class="text-stone-500 hover:underline"><?= $table['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php if ($table['in_session']): ?>
                    <p class="mt-3 text-xs text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 text-center">
                        Guests are dining here right now, so this table can't be taken out of service.
                        Close the session from the staff Table Status board first.
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
