<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/customer_topbar.php'; ?>
<main class="flex-1 flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-sm border border-amber-100 p-8 text-center">
        <div class="text-3xl mb-2">🎉</div>
        <h1 class="text-xl font-bold text-stone-800 mb-1">You're in, <?= e(CustomerSession::memberName()) ?>!</h1>
        <p class="text-sm text-stone-500 mb-4">Table <?= e($session['table_number']) ?> &middot; share this code with the rest of your table so they can order too:</p>

        <div class="text-4xl font-mono font-bold tracking-widest text-amber-700 bg-amber-50 border border-amber-200 rounded-xl py-4 mb-6">
            <?= e($session['session_code']) ?>
        </div>

        <a href="/index.php?route=order/menu" class="block py-3 rounded-xl bg-amber-600 text-white font-semibold hover:bg-amber-700">Start Ordering &rarr;</a>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
