<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/staff_navbar.php'; ?>
<main class="flex-1 max-w-4xl mx-auto w-full px-6 py-8">
    <h1 class="text-3xl text-stone-900"><?= e($pageTitle) ?></h1>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">
        <div class="bg-white rounded-xl border border-amber-100 p-5 shadow-sm text-center">
            <div class="text-2xl font-bold text-green-700"><?= money($revenue['total_revenue']) ?></div>
            <div class="text-xs text-stone-500 mt-1 uppercase">Total Revenue</div>
        </div>
        <div class="bg-white rounded-xl border border-amber-100 p-5 shadow-sm text-center">
            <div class="text-2xl font-bold text-red-600"><?= money($revenue['unpaid_amount']) ?></div>
            <div class="text-xs text-stone-500 mt-1 uppercase">Unpaid</div>
        </div>
        <div class="bg-white rounded-xl border border-amber-100 p-5 shadow-sm text-center">
            <div class="text-2xl font-bold text-amber-700"><?= (int)$revenue['bill_count'] ?></div>
            <div class="text-xs text-stone-500 mt-1 uppercase">Bills Generated</div>
        </div>
    </div>

    <section class="bg-white rounded-xl border border-amber-100 shadow-sm p-5 mt-6">
        <h2 class="font-semibold text-stone-800 mb-3">Popular Items</h2>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-stone-500 border-b border-stone-200"><th class="py-2">Item</th><th>Category</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
            <tbody>
            <?php foreach ($popularItems as $p): ?>
                <tr class="border-b border-stone-100">
                    <td class="py-2"><?= e($p['name']) ?></td>
                    <td><?= e($p['category_name']) ?></td>
                    <td><?= (int)$p['total_qty'] ?></td>
                    <td><?= money($p['total_revenue']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
