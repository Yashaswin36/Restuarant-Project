<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/staff_navbar.php'; ?>
<main class="flex-1 max-w-4xl mx-auto w-full px-6 py-8">
    <h1 class="text-3xl text-stone-900"><?= e($pageTitle) ?></h1>

    <?php if ($msg = flash_get('success')): ?><div class="mt-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash_get('error')): ?><div class="mt-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>

    <section class="bg-white rounded-xl border border-amber-100 shadow-sm p-5 mt-6">
        <h2 class="font-semibold text-stone-800 mb-3">Add Account</h2>
        <form method="POST" action="/index.php?route=admin/staff/store" class="grid grid-cols-2 gap-3">
            <?= Csrf::field() ?>
            <input type="text" name="name" placeholder="Name" required class="rounded-lg border border-stone-300 px-3 py-2">
            <select name="role" required class="rounded-lg border border-stone-300 px-3 py-2">
                <option value="staff">Staff</option>
                <option value="kitchen">Kitchen</option>
            </select>
            <input type="email" name="email" placeholder="Email" required class="rounded-lg border border-stone-300 px-3 py-2">
            <input type="password" name="password" placeholder="Temporary password" minlength="8" required class="rounded-lg border border-stone-300 px-3 py-2">
            <button type="submit" class="col-span-2 py-2 rounded-lg bg-amber-600 text-white font-semibold hover:bg-amber-700">Create Account</button>
        </form>
    </section>

    <section class="bg-white rounded-xl border border-amber-100 shadow-sm p-5 mt-6">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-stone-500 border-b border-stone-200"><th class="py-2">Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($accounts as $u): ?>
                <tr class="border-b border-stone-100">
                    <td class="py-2"><?= e($u['name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td class="capitalize"><?= e($u['role']) ?></td>
                    <td><span class="px-2 py-0.5 rounded-full text-xs <?= $u['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-stone-100 text-stone-500' ?>"><?= e($u['status']) ?></span></td>
                    <td>
                        <form method="POST" action="/index.php?route=admin/staff/status" class="inline">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button type="submit" class="text-amber-700 hover:underline"><?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
