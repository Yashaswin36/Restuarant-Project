<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/staff_navbar.php'; ?>
<main class="flex-1 max-w-6xl mx-auto w-full px-6 py-8">
    <h1 class="text-3xl text-stone-900"><?= e($pageTitle) ?></h1>

    <?php if ($msg = flash_get('success')): ?><div class="mt-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash_get('error')): ?><div class="mt-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <section class="bg-white rounded-xl border border-amber-100 shadow-sm p-5 lg:col-span-1 h-fit">
            <h2 class="font-semibold text-stone-800 mb-3"><?= $editingCategory ? 'Edit Category' : 'Add Category' ?></h2>
            <form method="POST" action="/index.php?route=admin/menu/categories/<?= $editingCategory ? 'update' : 'store' ?>" class="space-y-3">
                <?= Csrf::field() ?>
                <?php if ($editingCategory): ?><input type="hidden" name="id" value="<?= (int)$editingCategory['id'] ?>"><?php endif; ?>
                <input type="text" name="name" placeholder="Category name" value="<?= e($editingCategory['name'] ?? '') ?>" required
                       class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
                <input type="number" name="display_order" placeholder="Display order" value="<?= e((string)($editingCategory['display_order'] ?? '0')) ?>"
                       class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
                <button type="submit" class="w-full py-2 rounded-lg bg-amber-600 text-white font-semibold hover:bg-amber-700"><?= $editingCategory ? 'Save' : 'Add' ?></button>
            </form>
            <ul class="mt-4 divide-y divide-stone-100 text-sm">
                <?php foreach ($categories as $cat): ?>
                    <li class="py-2 flex justify-between items-center">
                        <span><?= e($cat['name']) ?> <span class="text-stone-400">(#<?= (int)$cat['display_order'] ?>)</span></span>
                        <span class="flex gap-2">
                            <a href="/index.php?route=admin/menu&edit_category=<?= (int)$cat['id'] ?>" class="text-amber-700 hover:underline">Edit</a>
                            <form method="POST" action="/index.php?route=admin/menu/categories/delete" class="inline" onsubmit="return confirm('Delete this category and its items?');">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="bg-white rounded-xl border border-amber-100 shadow-sm p-5 lg:col-span-2">
            <h2 class="font-semibold text-stone-800 mb-3"><?= $editingItem ? 'Edit Item' : 'Add Menu Item' ?></h2>
            <form method="POST" action="/index.php?route=admin/menu/items/<?= $editingItem ? 'update' : 'store' ?>" class="grid grid-cols-2 gap-3">
                <?= Csrf::field() ?>
                <?php if ($editingItem): ?><input type="hidden" name="id" value="<?= (int)$editingItem['id'] ?>"><?php endif; ?>
                <input type="text" name="name" placeholder="Item name" value="<?= e($editingItem['name'] ?? '') ?>" required
                       class="col-span-2 rounded-lg border border-stone-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
                <select name="category_id" required class="rounded-lg border border-stone-300 px-3 py-2">
                    <option value="">Category&hellip;</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= ($editingItem['category_id'] ?? null) == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" step="0.01" name="price" placeholder="Price (₹)" value="<?= e($editingItem['price'] ?? '') ?>" required
                       class="rounded-lg border border-stone-300 px-3 py-2">
                <input type="text" name="description" placeholder="Description" value="<?= e($editingItem['description'] ?? '') ?>"
                       class="col-span-2 rounded-lg border border-stone-300 px-3 py-2">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_veg" <?= ($editingItem['is_veg'] ?? 1) ? 'checked' : '' ?>> Vegetarian</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_available" <?= ($editingItem['is_available'] ?? 1) ? 'checked' : '' ?>> Available</label>
                <div class="col-span-2 flex gap-3">
                    <button type="submit" class="px-5 py-2 rounded-lg bg-amber-600 text-white font-semibold hover:bg-amber-700"><?= $editingItem ? 'Save' : 'Add Item' ?></button>
                    <?php if ($editingItem): ?><a href="/index.php?route=admin/menu" class="px-4 py-2 text-stone-500 hover:text-stone-700">Cancel</a><?php endif; ?>
                </div>
            </form>

            <table class="w-full text-sm mt-6">
                <thead><tr class="text-left text-stone-500 border-b border-stone-200">
                    <th class="py-2">Name</th><th>Category</th><th>Price</th><th>Veg</th><th>Available</th><th></th>
                </tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr class="border-b border-stone-100">
                        <td class="py-2"><?= e($item['name']) ?></td>
                        <td><?= e($item['category_name']) ?></td>
                        <td><?= money($item['price']) ?></td>
                        <td><?= veg_mark((bool)$item['is_veg']) ?></td>
                        <td><?= $item['is_available'] ? 'Yes' : 'No' ?></td>
                        <td class="flex gap-2 py-2">
                            <a href="/index.php?route=admin/menu&edit_item=<?= (int)$item['id'] ?>" class="text-amber-700 hover:underline">Edit</a>
                            <form method="POST" action="/index.php?route=admin/menu/items/delete" class="inline" onsubmit="return confirm('Delete this item?');">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
