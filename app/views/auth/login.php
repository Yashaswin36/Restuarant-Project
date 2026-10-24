<?php $pageTitle = 'Staff Login'; require __DIR__ . '/../partials/header.php'; ?>
<main class="flex-1 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-sm border border-amber-100 p-8">
        <div class="mb-7">
            <?= wordmark('text-xl') ?>
            <p class="eyebrow mt-3">Floor &middot; Kitchen &middot; Admin</p>
            <h1 class="text-2xl mt-1 text-stone-900">Sign in</h1>
        </div>

        <?php if ($error = flash_get('error')): ?>
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success = flash_get('success')): ?>
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="/index.php?route=login" class="space-y-4">
            <?= Csrf::field() ?>
            <div>
                <label class="block text-sm font-medium text-stone-600 mb-1">Email</label>
                <input type="email" name="email" required autofocus
                       class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-stone-600 mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
            <button type="submit" class="w-full py-2.5 rounded-lg bg-amber-600 text-white font-semibold hover:bg-amber-700">Login</button>
        </form>

        <details class="mt-6 text-xs text-stone-500">
            <summary class="cursor-pointer select-none">Demo credentials</summary>
            <table class="w-full mt-2 text-left border-collapse">
                <tr class="border-b border-stone-200"><th class="py-1">Role</th><th>Email</th><th>Password</th></tr>
                <tr class="border-b border-stone-100"><td class="py-1">Super Admin</td><td>admin@tablefare.test</td><td>Admin@123</td></tr>
                <tr class="border-b border-stone-100"><td class="py-1">Staff</td><td>staff1@tablefare.test</td><td>Staff@123</td></tr>
                <tr><td class="py-1">Kitchen</td><td>kitchen1@tablefare.test</td><td>Kitchen@123</td></tr>
            </table>
        </details>

        <p class="mt-6 text-center text-sm">
            <a href="/index.php" class="text-amber-700 hover:underline">&larr; Back to the guest site</a>
        </p>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
